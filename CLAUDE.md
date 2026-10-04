# CLAUDE.md

Project-specific notes for Pensec. Universal collaboration rules live in
[FOUNDATION.md](FOUNDATION.md) and always apply; this file records what is true of
**this** project only. Where the two disagree on a project matter, this file wins.

---

## 1. What Pensec is

Raspberry Pi probes are placed inside a customer network and run a full security
assessment locally. When every test has finished, a probe submits one complete JSON
report to this API. The API authenticates the probe, identifies the run and stores the
raw document without interpreting it. An administration panel manages probes and shows
the runs they produced.

The specification and a real reference report are in [docs/](docs/):
`specyfikacja.md`, `raport.json`.

Analysis of report contents is explicitly **not** part of this system yet. The raw
document is kept as the source of truth so that adding a parser later cannot cost data
already collected.

---

## 2. Environment

| | |
|---|---|
| PHP | **8.5.7** at `/usr/local/bin/php8.5` |
| Database | MariaDB 10.11 (`host473413_pensec`) |
| Framework | Laravel 13 |
| Node | 20.x |
| Web root | `public/`, already configured; the app is live at https://pensec.top |

**`php` on PATH is 8.3, not 8.5.** Always call `/usr/local/bin/php8.5` explicitly.
Composer's platform is pinned to 8.5.7 in `composer.json`, so dependencies resolve for
the version the application actually runs on regardless of which CLI invokes Composer.

`.env` is not in the repository. Production values there: `APP_ENV=production`,
`APP_DEBUG=false`, `LOG_LEVEL=warning`, `LOG_STACK=daily`.

### Building assets

`public/build` is gitignored, so `npm run build` is required after any change to
`resources/css`, `resources/js` or after introducing a Tailwind class that no view used
before. Editing Blade markup alone needs nothing - Blade compiles at runtime.

**Builds are dangerous on this host.** The process limit stops Rolldown from starting its
thread pool; `RAYON_NUM_THREADS=1` is baked into the `build` script, but a runaway build
still stalls the whole server. Run it capped (`timeout 120 npm run build`) and, if it does
not finish, hand the command to Rafał to run in his own terminal.

---

## 3. Commands

```bash
/usr/local/bin/php8.5 artisan test              # full suite, ~2 s
/usr/local/bin/php8.5 vendor/bin/pint           # code style
npm run lint:api                                # Spectral over the OpenAPI contract
/usr/local/bin/php8.5 artisan pensec:create-admin   # first panel account, asks for a password
/usr/local/bin/php8.5 artisan queue:work --stop-when-empty   # what the crontab runs each minute
```

Tests run against in-memory SQLite (see `phpunit.xml`). **Never cache config on this
machine** - a cached config makes `artisan test` ignore the phpunit environment and reach
for the production database.

---

## 4. Decisions that are not obvious from the code

### The contract comes before the code

`docs/OpenAPI/openapi.yaml` is hand-written and is the source of truth for the API. It was
written before the implementation and must be updated **before** an endpoint changes.
Scramble and any other generate-from-code approach was rejected: the standard in
`docs/OpenAPI/KONTRAKT-API-STANDARD.md` demands `oneOf`/`discriminator`, complete error
codes and schema-conformant examples, none of which a generator produces.

Two things keep the contract honest, and both must stay:

- `npm run lint:api` - Spectral, currently clean at `--fail-severity=warn`.
- Spectator in the feature tests. **Every API test asserts `assertValidResponse`.** Lint
  checks that the document is well-formed; only these assertions prove the code matches
  it. A new endpoint without them can drift silently.

### Report storage

- `reports` holds system data; the document is a gzip file on the `local` disk,
  `storage/app/private/reports/{report_uid}.json.gz`, named in `payload_path`. Files, not a
  column, because a row is sent to MariaDB whole (capped by `max_allowed_packet`, 256 MB)
  and held in PHP memory on the way, and reports now run to hundreds of megabytes.
- `report_payloads` was the old home of the documents. It was dropped on 2026-10-04 once
  the files had proved out; the files are the only copy.
- `payload_format` says what a file holds. `submission` (everything since 2026-10-01) is
  the request body exactly as sent - envelope included, gzip removed - so `payload_bytes`
  and `payload_sha256` match the probe's own file. `report` is the older form: the `report`
  object alone, canonically re-encoded. `Report::document()` returns the report either way.
- Submission is idempotent on `report_uid`. The first document wins; a repeat answers
  `200` with what is on file, **even when its content differs** - the contract says so, and
  the probe retries anything that is not 2xx for ever. The race where two submissions pass
  the lookup together is settled by the unique index and handled in `ReportIntake`.

**A report body is never decoded on the way in.** `json_decode` costs about 5.6 times the
document, and the old path did it twice - once inside Laravel, which decodes every JSON body
while building the request, and once in the middleware - so anything over ~37 MB died at
`memory_limit` (512 MB). Now:

- `public/index.php` captures `App\Http\IncomingRequest`, which leaves the decoded input of
  `POST /api/v1/reports` empty. The test client does not go through it;
  `IncomingRequestTest` covers it on its own.
- `StreamReportUpload` streams the body, inflates gzip chunk by chunk and enforces
  `max_payload_bytes` on the uncompressed size while it grows (a gzip bomb is refused before
  it is held). The document is then held **once**: `json_validate()` checks it without
  building values, and `App\Support\JsonOutline` finds `report_id`, the type of `report` and
  `scan_time` by byte offset. `UploadedReport` carries that to the request and `ReportIntake`.
- `JsonOutline` is only correct on input `json_validate()` accepted - that is what lets it
  skip a value by counting brackets. Never hand it anything else.
- `IncomingRequest::getContent()` also answers `''` for that route: Laravel calls it when it
  builds the FormRequest (`Request::createFrom`), which would otherwise hold a second copy.
- Multi-member gzip (`cat a.gz b.gz`, pigz) is read whole; bytes after the end that are not
  another member are refused. A body over PHP's `post_max_size` is mapped to the 413 envelope
  in `bootstrap/app.php`.
- `ReportIntake` checks both the write and the move of the file; a failure is a 500, never a
  `201` for a row pointing at nothing.
- Peak memory is about the document plus a few megabytes: a 55 MB report stores in 0.6 s on
  59 MB. `test_a_large_report_is_stored_without_decoding_it` holds that line.

**Reading is bounded too.** `ReportFacts::forReport()` goes through `App\Support\ReportSections`,
which holds the document once as a string and decodes only the top-level keys listed in
`ReportFacts::SECTIONS`, each on its own. Nuclei's findings - the part that grows with the
network, 13 549 entries and 90% of a real report - are never decoded at once: they arrive as
a generator and are folded as they are read. A key `ReportFacts` reads but `SECTIONS` does
not list silently reads as absent; keep the two in step. `Report::document()` still decodes
everything and is for tests and small documents only.

**`scanned_at` is the one field read out of the document on the way in**, and it exists for
a single question: did the probe scan late, or did it hold a finished report? Without it
the panel shows only `received_at`, so a document that sat on a device for four days
arrives at the top of the list wearing today's date. `ReportIntake` fills it through
`App\Support\ScanTime`; the panel shows it beside the receipt with the gap between them.
This is not the parser: nothing else is interpreted, and `Report::card()` does not carry
it, so the API contract is untouched.

The probe writes `scan_time` with **no zone in it**, so one has to be assumed before it can
be compared with `received_at`, which is UTC. `pensec.reports.probe_timezone` holds the
assumption (`Europe/Warsaw`) and is the first thing to check if every delay is suddenly two
hours out. `ScanTime` accepts only the formats it knows and returns null for anything else,
including a 1970 date - a Raspberry Pi has no clock of its own, and a probe that never
reached NTP would otherwise report a delay measured in decades. Null renders as "nie
podano" rather than as a zero.

### Devices and authentication

- No Sanctum. Device tokens are 64 hex characters, stored only as an unsalted SHA-256
  digest - unsalted deliberately, because lookup is by hash.
- `token_prefix` exists so the panel can tell two probes apart without being able to
  reconstruct a token.
- A device carrying reports cannot be deleted (`restrictOnDelete` plus a guard in the
  controller). Reissuing a token exists because otherwise a lost token would leave such a
  device permanently unusable.

### API responses

`App\Support\ApiResponse` is the only place the envelope is built, and
`App\Enums\ApiErrorCode` is the only source of error codes. **A code, once published in
the contract, must not change meaning.** Failures are mapped in `bootstrap/app.php`, so
validation, throttling and unexpected exceptions all leave in the same shape.

`Report::card()` is the single serialisation of a report. Anything returning a report
delegates to it.

### Languages

- API messages: English (`lang/en/api.php`) - the audience is firmware.
- Panel: Polish (`lang/pl/panel.php`), locale switched per request by the
  `panel.locale` middleware, so the API keeps answering in English.
- Landing page: Polish copy inline in `resources/views/home.blade.php`. It is page
  content rather than reusable UI strings, and a `pl` app locale would contradict the
  API's `en`.
- Documentation in `docs/`: English.

### PDF reports

A report yields two documents - `expert` and `client`. Same scan, same figures, different
wording. Both live in `report_narratives`, one row per report per variant, unique on the
pair.

**The model never supplies a number.** `ReportFacts` derives every figure from the stored
document - hosts, open ports, findings, ICS endpoints - and the same array feeds both the
tables in `resources/views/pdf/` and the brief the model is given. DeepSeek writes prose
around facts it was handed and sees nothing else, so it cannot name a host that was not
discovered. `NmapOutput` is what makes this possible: the probe stores nmap's console
output verbatim, and that text is parsed rather than shown. If a section's prose is missing,
the facts still render.

**"Nothing found" and "did not run" are different results and must never render alike.**
`ReportFacts::exposure()` classifies each check as absent, failed, clean, or carrying
findings. This is not theoretical: on a real report every nuclei entry was an error
(`no templates provided for scan`), and counting those entries as findings put "5 trafień
skanowania szablonami" into a document where the scanner had produced nothing. The brief
says so in as many words, because a model shown a bare count will report it as a result.

**Severity is graded in code.** `App\Support\Severity` turns evidence into weight and
`ReportFacts::ranked()` gathers every section's findings into one ordered list, so the
document opens with what to do first instead of six sections of equal-looking text. The
model is handed the ranking and told not to re-grade anything.

The grading is deliberately cautious, and one rule carries most of the value: **nmap's own
confidence wins.** `http-phpmyadmin-dir-traversal` prints `VULNERABLE:` and a CVE and then
admits `State: UNKNOWN (unable to test)` - that is graded medium, not critical, because the
script announced what it looks for and then failed to confirm it. A script that timed out or
errored is not a finding at any weight: it joins `gaps`, which is kept apart from `findings`
so a hole in the coverage can never be sorted below the problems it may be hiding.

**The closing section is chosen from the evidence.** `facts['plan']` is `repair` when
anything is actionable **or** any coverage gap exists, `maintain` only when neither is true -
a badanie with holes never earns the congratulatory ending, because nobody can vouch for what
was not examined. The template, not the model, writes the standing final bullet, so it
appears on every report word for word.

**The two variants differ in register, not in facts.** Expert is impersonal and passive with
recommendations as verbal nouns ("Wyłączenie obsługi SSLv3 na 192.168.100.1"); client is
plain second person with the reason it matters. Both must attribute every finding to a
specific IP where the brief supplies one.

**Diagnostics are described, not dumped.** The probe sends these as small objects of
technical flags, and `{"gratuitous_arp_blocked": false}` printed as key and value tells a
reader nothing - worse, a bare `false` looks like a failure when it is sometimes the good
outcome and here is the bad one. `App\Support\Diagnostics` holds the sentence each known
flag means in both directions plus which way round is a concern; `TsharkEndpoints` parses
the `top_talkers` console dump into a real table. Unknown keys and fields still render under
a humanised name, so a new test on the probe appears in the report rather than vanishing.

**Generation is queued, rendering is not.** `POST …/narrative/{variant}` only enqueues
`GenerateReportNarrative`; the row exists as `pending` first so the panel has something to
poll. The PDF route renders from what is on file and never waits on a model, which is why a
second download is free and gives the same document. Re-generating is a separate,
confirmed action.

**The facts are kept with the prose.** The job stores the facts it handed the model in
`report_narratives.facts`, and the PDF renders its tables from that snapshot, not from a
fresh reading. The document never changes, so the snapshot holds nothing that is not in it;
what it buys is that tables and prose always describe the same numbers, even after
`ReportFacts` learns to read a report better. A narrative written before snapshots existed
gets one on its first download - its prose then sits beside the newer reading. The
narratives from before 2026-10-04 are left that way on purpose: they are test runs of no
value, so do not spend DeepSeek calls regenerating them. Regenerating clears the snapshot.

**Schema 3 reports say which tests ran, and that is what coverage is built from.**
`audit_group_status` lists 19 test groups, but the probe marks a group `failed` when any one
path failed - a port scan that worked on twenty hosts and timed out on three is `failed`. So
the evidence paths are read instead: a group with no `ok` path never ran; otherwise the
hosts it failed on are named. One gap per group, listing hosts - not one per host per
module, which once turned 182 timeouts into a 30-page PDF. Reports without groups fall back
to `module_status` folded per module. `report_summary.overall_result` (`operator_stop`,
`completed_with_module_errors`) opens the gaps and appears on the cover.

Schema 3 also writes `[]` for a test that was stopped before it started, so once a report
carries groups at all, an empty exposure module is only "clean" when its group
(`EXPOSURE_MODULES`) says it ran; a group skipped everywhere makes it "nie dotyczy"; anything
else is "test nie wykonał się". Only the oldest reports, with no groups, still read `[]` as
clean. Results in a `timeout`/`failed`/`blocked` state, and any `*_FAILED` type
(`RESPONDER_START_FAILED` carries severity HIGH), are errors; `skipped`/`not_applicable` are
nothing; `ok` without a severity is a clean check; `no_observation` counts as a check only
when the module reported no error. Older `module_status` names are folded into the same
groups (`MODULE_GROUPS`), so section notes work for both generations.

An NSE script is a gap ("nie uzyskał odpowiedzi") only on nmap's own failure words - an
`ERROR:` line, "Script execution failed", or a short output that is just a timeout. The
output also carries the target's reply, and a router page saying "The session is timeout."
once demoted two real findings. The PDF partials put
`pdf.partials._pokrycie` above every section whose test did not run in full, and drop their
"nothing found" sentence when it did not run at all.

**Correlations are folded and graded down.** `cve_correlations` lists every CVE matched to a
software version on its own - 44 lines for one router. They are folded into one finding per
software per host, graded by the worst CVSS, and - being version matches nobody exploited -
one step lower than the score says (`Severity::ofCvss`), the same rule as an NSE script that
could not confirm itself. Where they exist, the `vulners` script findings for that host are
dropped, because the correlation is built from them. Nuclei matches are folded per template
and host with a count.

**Lists are whole in the facts and cut where they are shown.** The PDF and the brief cap long
lists (findings 60/80, a module's results 15/40, CVEs 8/10) and always say how many were left
out; counts are never computed from a cut list. dompdf embeds font subsets
(`isFontSubsettingEnabled`): 165 KB instead of 1.2 MB per PDF.

**The queue needs its worker.** `QUEUE_CONNECTION=database` and a crontab entry runs
`queue:work --stop-when-empty --timeout=330` every minute. Without it a click leaves the
report `pending` for ever. The chain is `queue.connections.database.retry_after` (360) >
worker `--timeout` (330) > the job's `$timeout` (300) > `services.deepseek.timeout` (240).
`retry_after` is the one that was missed: at Laravel's default of 90 the next minute's
worker picked a slow job up again and, with `--tries=1`, failed it while it still ran.

Two DeepSeek settings are scars, not preferences, and both are commented where they live:
`max_tokens` is never sent (v4 reasoning tokens count against it and truncate the report
silently), and `reasoning_effort` is pinned low. The same lesson is recorded in
`device.ursalogic.pl`, which is worth reading before changing either.

dompdf cannot read WebP, so the cover uses `public/images/pensec-logo-print.png` - flattened
onto white, no alpha. Polish diacritics need DejaVu Sans; do not swap the font family
without checking them.

One Blade trap cost real time here: a directive glued to a word character
(`…czystym@endif`) is left uncompiled and surfaces as `syntax error, unexpected end of
file`. Keep whitespace before `@if`/`@endif` in prose.

### Light and dark theme

One token set serves both themes. `@theme` in `resources/css/app.css` defines the dark
values; `:root[data-theme='light']` re-points **the same names**, so no utility class
carries a theme variant and no view knows which theme is on. Anything added must be
expressed in those tokens - `ink`, `ink-raised`, `ink-line`, `brand`, `brand-deep`,
`chrome`, `muted`, `warn`, `warn-line`, `warn-soft`. A literal colour or a `text-amber-300`
will only be right in one theme.

Gradients, glows and the grid cannot be written as utilities, so they read plain custom
properties (`--glow-near`, `--card-face-top`, `--chrome-text-top`, `--logo-glow`…) that
the light block re-points as well.

The choice is stored in `localStorage` under `pensec.theme`; with nothing stored the
system preference decides. `partials/theme-boot` is included **before** the stylesheet and
runs synchronously, so the stored theme is on `<html>` before the first paint - moving it
lower, or into a bundle, brings the flash back. `<html data-theme="dark">` in the markup is
what a browser without JavaScript keeps.

Both logos ship in the markup and CSS hides the one that does not apply
(`theme-when-dark` / `theme-when-light`), so the right one is correct on the first paint
without a JavaScript `src` swap. The light files are built onto the same canvas as the dark
ones - `768x256` for the logo, `512x590` for the mark, content scaled to the same fraction
of it - so switching moves nothing on the page. Sources live in `resources/images/`;
everything in `public/images/` is derived from them.

### Landing page

Deliberately contains **nothing about the API** - no endpoints, no tokens, no contract.
It is a client-facing page; the technical layer is internal. Keep it that way when
editing. Its "Stan prac" section describes real status, so update it when that changes.

### Panel

Every account has full access. There are no roles: an administrator can manage probes,
read every report and create or remove other administrators. Removing your own account is
refused.

Changing a password does **not** require the current one - deliberate, at Rafał's request,
because an administrator signed in for weeks rarely remembers it. Every other session is
dropped when the password changes - which only works because the panel's `auth` group also
carries `auth.session`; `Auth::logoutOtherDevices()` alone re-hashes and nothing more.

### Documentation URLs

- `/api/openapi.yaml` serves the contract file directly, so there is no second copy.
- `/docs/{slug}.html` serves any `.html` file in `docs/`. The slug pattern
  `[A-Za-z0-9_-]+` is the whole defence: it admits no slash and no dot, so nothing outside
  the directory and nothing but `.html` is reachable. `DocumentationTest` pins this down;
  keep those cases if the route is touched.
- The reference UI (Scalar) is vendored in `public/vendor/scalar/` rather than loaded from
  a CDN.

### Tunables

`config/pensec.php` holds the business constants: 256 MB report limit (uncompressed), 30 submissions per
minute per probe, token length. They are config, not `.env`, and not class constants.

---

## 5. Deliberately not built

- **Code map** (`code-map.html`, FOUNDATION §4) - skipped by decision: the API is a single
  endpoint and the map would cost more to maintain than it returns.
- **Discord alerting** (FOUNDATION §5) - deferred, to be picked up later.
- **Report parsing in the API** - still a later stage. `ReportFacts` reads the document to
  build a PDF, but nothing is parsed on the way in and nothing derived is stored:
  `ReportStatus` still has one value, `received`. New values are added as parsing lands,
  and the contract's enum must grow with it.
- **Password reset for another administrator** - an administrator sets the initial
  password when creating an account; the account owner changes it themselves.
