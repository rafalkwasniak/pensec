# Standard kontraktu API (OpenAPI) — Definition of Done

Dokument uniwersalny. Nie opisuje żadnego konkretnego API, tylko to, **co musi
znaleźć się w kontrakcie, żeby dało się z niego wygenerować klienta i napisać
front bez pytania nikogo o nic.**

Odbiorca: backend piszący kontrakt, frontend go odbierający, reviewer w PR.

---

## 0. Zasada naczelna

> **Kontrakt jest źródłem prawdy o typach, a nie dokumentacją dla ludzi.**

Wszystko, co jest wyłącznie w `description`, dla generatora nie istnieje.
`description` czyta człowiek raz. `schema` czyta kompilator przy każdym buildzie.

Trzy pytania, które przechodzą przez cały ten dokument:

1. **Czy pole na pewno przyjdzie?** → `required`
2. **Czy pole może być puste i w jaki sposób?** → `nullable` vs `""` vs brak klucza
3. **Jakie wartości może przyjąć?** → `enum`, `format`, `minimum`, `pattern`

Jeśli kontrakt nie odpowiada na te trzy pytania dla każdego pola, front zgaduje.
Zgadywanie kończy się `any`, opcjonalnymi `?` wszędzie i defensywnym kodem, który
nigdy się nie wykona — albo produkcyjnym crashem, bo jednak `null` przyszedł.

**Kryterium akceptacji kontraktu:** czy da się z niego napisać ekran bez
odpalania backendu i bez pytania na Slacku. Jeśli nie — kontrakt nie jest gotowy.

---

## 1. DRY — zero schematów inline

### R1.1 Każdy schemat odpowiedzi i requestu to `$ref`

Generatory (Orval, openapi-typescript, openapi-generator) **nie wykrywają, że dwa
identyczne schematy inline to ten sam byt.** Widzą dwie niezależne definicje i
robią dwa typy.

❌ Źle — dwie niezależne definicje tego samego użytkownika:

```yaml
paths:
  /me:
    get:
      responses:
        "200":
          content:
            application/json:
              schema:
                type: object
                properties:
                  id: { type: integer }
                  firstname: { type: string }
                  # ... 40 linii
  /user/{id}:
    get:
      responses:
        "200":
          content:
            application/json:
              schema:
                type: object
                properties:
                  id: { type: integer }
                  firstname: { type: string }
                  # ... te same 40 linii, copy-paste
```

Wynik w kodzie frontu: `GetCurrentUser200` i `GetUser200` — dwa typy o niemal
identycznej treści, niekompatybilne ze sobą. Funkcja `renderUser(u: GetUser200)`
nie przyjmie wyniku z `/me`. Przy 16 endpointach i zagnieżdżonych
`translates` / `products[].catalog` robi się z tego kilkaset typów-klonów.

✅ Dobrze:

```yaml
paths:
  /me:
    get:
      operationId: getCurrentUser
      responses:
        "200":
          description: Zalogowany użytkownik
          content:
            application/json:
              schema: { $ref: '#/components/schemas/User' }
  /user/{id}:
    get:
      operationId: getUser
      responses:
        "200":
          description: Użytkownik o podanym id
          content:
            application/json:
              schema: { $ref: '#/components/schemas/User' }
```

Wynik: jeden `interface User`, obie funkcje zwracają ten sam typ. To dokładnie
to samo, co wyciągnięcie interfejsu do wspólnego pliku i reużycie go.

### R1.2 To samo dotyczy parametrów i nagłówków

Nagłówki typu `League-Id`, `Accept-Language`, `X-Request-Id` powtarzają się przy
każdej operacji. Powtórzony inline = powtórzony w każdej wygenerowanej sygnaturze,
a jak zmieni się opis albo `required`, trzeba poprawić w 98 miejscach.

✅ Dobrze:

```yaml
components:
  parameters:
    LeagueId:
      in: header
      name: League-Id
      required: true
      description: >
        Id ligi (tenanta). Wymagany. Bez niego middleware podstawia ligę 0,
        która nie istnieje — dostaniesz 200 z pustą listą, a nie błąd.
      schema: { type: integer, minimum: 1 }
      example: 5
    AcceptLanguage:
      in: header
      name: Accept-Language
      required: false
      description: Id języka (liczba, nie kod ISO). Lista pod `GET /api/lang`.
      schema: { type: integer, minimum: 1 }
      example: 1

paths:
  /api/ranking:
    get:
      parameters:
        - { $ref: '#/components/parameters/LeagueId' }
        - { $ref: '#/components/parameters/AcceptLanguage' }
```

### R1.3 Reużywalne są też odpowiedzi błędów

```yaml
components:
  responses:
    NotFound:
      description: Zasób nie istnieje albo funkcja wyłączona w tej instalacji
      content:
        application/json:
          schema: { $ref: '#/components/schemas/ErrorMessage' }
    Unauthorized:
      description: Brak albo nieważny token
      content:
        application/json:
          schema: { $ref: '#/components/schemas/ErrorMessage' }

paths:
  /api/ranking/{id}:
    get:
      responses:
        "404": { $ref: '#/components/responses/NotFound' }
```

### R1.4 Nazwy schematów są nazwami typów w TypeScript

`PascalCase`, rzeczownikowe, bez sufiksów `200` / `Response` / `Dto`, chyba że to
naprawdę osobny kształt.

| Zamiast | Nazwij |
|---|---|
| `GetUser200`, `UserResponse` | `User` |
| `RankingItem1`, `RankingItem2` | `RankingUserRow`, `RankingAggregateRow` |
| `Object3`, `InlineObject12` | cokolwiek z domeny |

Zestaw nazw dla typowego modułu (przykładowo, ranking):
`RankingMeta`, `RankingDisplay`, `RankingUserRow`, `RankingAggregateRow`,
`RankingMemberRow`. Każdy z nich raz, reużyty wszędzie — w liście i w detalu.

---

## 2. Kompozycja — `allOf`, `oneOf`, `discriminator`

### R2.1 Wspólna część → `allOf`

Gdy detal to lista + kilka pól więcej, nie kopiuj listy.

```yaml
components:
  schemas:
    ArticleListItem:
      type: object
      required: [id, title, created_at]
      properties:
        id: { type: integer }
        title: { type: string }
        created_at: { type: string, format: date-time }

    ArticleDetail:
      allOf:
        - $ref: '#/components/schemas/ArticleListItem'
        - type: object
          required: [body]
          properties:
            body: { type: string }
            attachments:
              type: array
              items: { $ref: '#/components/schemas/Attachment' }
```

### R2.2 Warianty → `oneOf` + `discriminator`

To najczęstszy brak w kontraktach: jeden endpoint zwraca różne kształty w
zależności od typu wiersza, a schemat opisuje tylko jeden z nich — resztę
zostawia w `description`. **Opis to nie schemat.**

❌ Źle — `description` mówi „dla `group`/`level`/`league` przychodzi `name` i
`members`", a `properties` mają tylko kształt użytkownika i brak opcjonalnych
`nick`, `avatar`, `email`, `phone`:

```yaml
ranking:
  type: array
  description: >
    Dla row_type = user pola firstname/lastname, dla group/level/league
    pola name i members.
  items:
    type: object
    properties:
      row_type: { type: string }
      firstname: { type: string }
      lastname: { type: string }
      points: { type: number }
```

✅ Dobrze — wariant jest w typie, nie w prozie:

```yaml
components:
  schemas:
    RankingRowType:
      type: string
      enum: [user, group, level, league]

    RankingRowBase:
      type: object
      required: [row_type, position, points]
      properties:
        row_type: { $ref: '#/components/schemas/RankingRowType' }
        position: { type: integer, minimum: 1 }
        points:   { type: number }

    RankingUserRow:
      allOf:
        - $ref: '#/components/schemas/RankingRowBase'
        - type: object
          required: [id, firstname, lastname]
          properties:
            id:        { type: integer }
            firstname: { type: string }
            lastname:  { type: string }
            nick:      { type: string, nullable: true }
            avatar:    { type: string, format: uri, nullable: true }
            email:     { type: string, format: email, nullable: true }
            phone:     { type: string, nullable: true }

    RankingAggregateRow:
      allOf:
        - $ref: '#/components/schemas/RankingRowBase'
        - type: object
          required: [id, name, members]
          properties:
            id:      { type: integer }
            name:    { type: string }
            members: { type: integer, minimum: 0 }

    RankingRow:
      oneOf:
        - $ref: '#/components/schemas/RankingUserRow'
        - $ref: '#/components/schemas/RankingAggregateRow'
      discriminator:
        propertyName: row_type
        mapping:
          user:   '#/components/schemas/RankingUserRow'
          group:  '#/components/schemas/RankingAggregateRow'
          level:  '#/components/schemas/RankingAggregateRow'
          league: '#/components/schemas/RankingAggregateRow'
```

Co to daje frontowi: `if (row.row_type === 'user')` zawęża typ i TS **sam** wie,
że w tej gałęzi jest `firstname`, a w drugiej `name` i `members`. Bez
`discriminator` trzeba pisać ręczne type guardy.

Ta sama reguła dotyczy każdej listy, która ma warianty — również `data[]` w
endpointach składu/członków.

### R2.3 Kiedy `oneOf`, a kiedy jeden obiekt z opcjonalnymi polami

| Sytuacja | Rozwiązanie |
|---|---|
| Kształty się wykluczają, jest pole rozróżniające | `oneOf` + `discriminator` |
| Kształty się wykluczają, brak pola rozróżniającego | `oneOf` — ale **dodaj pole rozróżniające**, to tańsze niż każdy inny wariant |
| Jeden kształt, część pól opcjonalna zależnie od uprawnień | jeden obiekt + precyzyjne `required` + `nullable` |

Jeśli wybierasz jeden wspólny obiekt z wszystkimi polami opcjonalnymi, **musisz w
zamian dać jasne `required` per wariant** — inaczej front dostaje typ, w którym
wszystko jest `?` i nic nie da się bezpiecznie wyrenderować.

---

## 3. Typy — zamknięte zbiory, formaty, zakresy

### R3.1 `enum` zamiast gołego `string`

Każde pole o skończonym zbiorze wartości ma `enum`. Bez wyjątków.

❌ `row_type: { type: string }`
✅ `row_type: { type: string, enum: [user, group, level, league] }`

Typowe kandydatury: `*_type`, `status`, `state`, `mode`, `kind`, `role`,
`action`, `aggregate` (`sum` / `avg`), pola `display.*` (`none` / `initial` / `full`),
kierunek sortowania, kanał powiadomienia.

Enum reużywalny wyciągnij do `components/schemas` — będzie jednym typem TS w całym
projekcie zamiast pięciu literałów union.

**Kryterium:** jeśli w `switch` na froncie wypisujesz wartości ręcznie z opisu
albo z Slacka — brakuje enuma.

### R3.2 `format` tam, gdzie istnieje

| Pole | `type` | `format` |
|---|---|---|
| `created_at`, `snapshot_at`, `deleted_at` | `string` | `date-time` |
| `active_from` (sama data) | `string` | `date` |
| `email` | `string` | `email` |
| `avatar`, `url`, `image` | `string` | `uri` |
| `uuid` | `string` | `uuid` |
| id numeryczne > 2^31 | `integer` | `int64` |

Bez `format: date-time` front dostaje `string` i sam musi się domyślić, czy to
ISO 8601, czy `2026-08-12 10:00:00`, czy timestamp. Dopisz też strefę w opisie
(UTC czy lokalna) — tego `format` nie niesie.

### R3.3 Zakresy i ograniczenia

`minimum`, `maximum`, `minLength`, `maxLength`, `pattern`, `minItems`. To nie
kosmetyka: `maxLength` na polu formularza to gotowa walidacja po stronie klienta,
której nie trzeba wyklikiwać z backendem.

### R3.4 `integer` vs `number`

`integer` gdy to liczba całkowita. `number` gdy zmiennoprzecinkowa. Dla kwot i
punktów, gdzie liczy się precyzja, rozważ `type: string` z `pattern` i podaj to
jawnie — float w JS to `double` i `0.1 + 0.2 !== 0.3`.

---

## 4. `required`, `nullable`, `""` — trzy różne rzeczy

To najczęstsze źródło produkcyjnych crashy z kontraktu.

| Zapis | Znaczenie | Typ w TS |
|---|---|---|
| w `required`, bez `nullable` | klucz zawsze jest, wartość nigdy nie `null` | `x: string` |
| w `required`, `nullable: true` | klucz zawsze jest, wartość bywa `null` | `x: string \| null` |
| poza `required` | klucza może w ogóle nie być | `x?: string` |
| poza `required` + `nullable: true` | klucza może nie być **albo** jest `null` | `x?: string \| null` |

### R4.1 `required` musi być na każdym obiekcie

Obiekt z `properties` bez `required` generator czyta jako „wszystko opcjonalne".
Front dostaje typ z samymi `?` i nie może wyrenderować niczego bez `if`-a.
Jeśli pole zawsze przychodzi — wpisz je do `required`. To nie jest deklaracja
„pole jest niepuste", tylko „klucz istnieje w odpowiedzi".

### R4.2 `nullable: true` wszędzie, gdzie API realnie zwraca `null`

❌ `snapshot_at: { type: string, format: date-time }` — a API zwraca `null`
✅ `snapshot_at: { type: string, format: date-time, nullable: true }`

Klasyczne kandydatury: `deleted_at`, `snapshot_at`, `closed_at`, `avatar`,
`nick`, wszystko z relacji opcjonalnej.

**Uwaga na wersję OpenAPI:**

- **3.0.x** → `nullable: true`
- **3.1.x** → `type: [string, "null"]` (`nullable` w 3.1 nie istnieje i jest ignorowane)

Jeden plik = jedna konwencja. Mieszanka to cicha awaria: `nullable` w pliku 3.1
nie zgłosi błędu, po prostu nie zadziała.

### R4.3 Ujednolić `null` vs `""`

Decyzja projektowa, podjęta **raz dla całego API** i zapisana w kontrakcie:

- **Rekomendacja:** brak wartości = `null`. Pusty string zarezerwowany na
  „użytkownik świadomie wpisał pustkę" (a zwykle nie istnieje taki przypadek).
- Jeśli backend zwraca `""` dla braku — to musi być w opisie pola, bo
  `if (!user.nick)` i `if (user.nick === null)` to na froncie różny kod.

Nie może być tak, że jedno pole zwraca `null`, drugie `""`, a trzecie `"—"`.

---

## 5. Błędy

### R5.1 Wszystkie kody, nie wybrane

Kontrakt wymienia **każdy** kod, który operacja może zwrócić. Jeśli w opisie
pada „409 to X albo Y", to obie te rzeczy muszą być w schemacie — nie jedna.

Minimum dla operacji chronionej: `200/201`, `400` lub `422`, `401`, `403`, `404`,
plus kody specyficzne dla operacji (`409`, `429`).

### R5.2 Kilka kształtów pod jednym kodem → `oneOf`

Jeśli pod `409` schodzi **dokładnie jeden** z dwóch kształtów, to jest `oneOf`:

```yaml
"409":
  description: >
    Zamówienia nie da się zrealizować. Przychodzi dokładnie jeden z dwóch
    kształtów — rozpoznawaj po kluczu, nie po kodzie.
  content:
    application/json:
      schema:
        oneOf:
          - title: Brak towaru
            type: object
            required: [incorrect_quantity]
            properties:
              incorrect_quantity:
                type: array
                description: Id produktów, których nie da się wydać w zamówionej ilości.
                items: { type: integer }
            example: { incorrect_quantity: [5] }
          - title: Za mało punktów
            type: object
            required: [too_few_points]
            properties:
              too_few_points:
                type: number
                description: Aktualne saldo punktowe użytkownika w chwili odmowy.
            example: { too_few_points: 120.5 }
```

Jeśli natomiast oba błędy mogą przyjść **naraz** — to nie `oneOf`, tylko jeden
obiekt z dwoma opcjonalnymi kluczami, i trzeba to napisać wprost. **Backend musi
odpowiedzieć na to pytanie w kontrakcie**, bo front inaczej nie wie, czy
renderować jeden komunikat, czy listę, i dowie się dopiero produkcyjnie.

### R5.3 Kod maszynowy, nie tekst

Front nie może rozpoznawać błędu po treści komunikatu — treść zmienia się przy
tłumaczeniu i przy każdej korekcie copy.

```yaml
Error:
  type: object
  required: [code, message]
  properties:
    code:
      type: string
      enum: [validation_failed, too_few_points, incorrect_quantity, not_found]
      description: Stabilny identyfikator błędu. Po tym rozgałęziaj logikę.
    message:
      type: string
      description: Tekst dla użytkownika. Może się zmienić bez zmiany wersji API.
    fields:
      type: object
      nullable: true
      additionalProperties:
        type: array
        items: { type: string }
```

Standard branżowy dla kształtu błędu: **RFC 9457 Problem Details**
(`application/problem+json`, pola `type` / `title` / `status` / `detail` /
`instance` + własne rozszerzenia). Zastąpił RFC 7807. Warto wziąć, jeśli
zaczynacie nowe API — ale ważniejsze jest, żeby kształt był **jeden dla całego
API**, niż żeby był akurat ten.

---

## 6. Koperta odpowiedzi, listy, paginacja

### R6.1 Jedna koperta, opisana raz

Jeśli API zawsze zwraca `{ respond, data, paginate }` albo
`{ success, message, data }` — to jest jeden schemat, nie 25 kopii.

W OpenAPI 3.x nie ma generyków, więc kopertę składa się przez `allOf`:

```yaml
components:
  schemas:
    Envelope:
      type: object
      required: [success, message]
      properties:
        success: { type: boolean }
        message: { type: string, nullable: true }

    Pagination:
      type: object
      required: [current_page, last_page, per_page, total]
      properties:
        current_page: { type: integer, minimum: 1 }
        last_page:    { type: integer, minimum: 1 }
        per_page:     { type: integer, minimum: 1 }
        total:        { type: integer, minimum: 0 }

    UserListResponse:
      allOf:
        - $ref: '#/components/schemas/Envelope'
        - type: object
          required: [data, pagination]
          properties:
            data:
              type: array
              items: { $ref: '#/components/schemas/User' }
            pagination: { $ref: '#/components/schemas/Pagination' }
```

Nadal jest jeden `XxxListResponse` per zasób, ale koperta i paginacja są
zdefiniowane raz. Alternatywa dla dużych API: generować kopertę skryptem
albo trzymać ją poza kontraktem jako helper typu w kliencie.

### R6.2 Puste listy

Pusta lista to `[]`, nie `null` i nie `{}`. Jeśli backend zwraca mapę
(`{ "ORD-1": {...} }`), pusty przypadek to `{}` — i to musi być w opisie,
bo `Object.keys()` vs `.map()` to inny kod.

### R6.3 Parametry listowania są w kontrakcie

`page`, `per_page`, `sort`, `order`, `search`, filtry — z typami, wartościami
domyślnymi i `enum` dla dozwolonych pól sortowania:

```yaml
- in: query
  name: sort
  schema:
    type: string
    enum: [created_at, points, position]
    default: position
- in: query
  name: order
  schema:
    type: string
    enum: [asc, desc]
    default: asc
```

---

## 7. Higiena dokumentu

### R7.1 `operationId` na każdej operacji

To dosłownie nazwa funkcji w wygenerowanym kliencie. Bez niego generator wymyśli
`getApiRankingIdRow`. `camelCase`, czasownik + zasób: `listRankingRows`,
`getCurrentUser`, `createOrder`.

### R7.2 `description` na każdej odpowiedzi

`description: ""` to pusty slot w dokumentacji. Napisz, **co ten kod znaczy
biznesowo**, szczególnie przy 404 („funkcja wyłączona w instalacji" to co innego
niż „nie ma takiego id").

### R7.3 `example` / `examples`

Przykład służy do dwóch rzeczy: czyta go człowiek i mockuje z niego Prism/MSW.
Przykład **musi być zgodny ze schematem** — niezgodny jest gorszy niż żaden,
bo wprowadza w błąd. Spectral to sprawdza (`oas3-valid-schema-example`).

### R7.4 Nazewnictwo — jedna konwencja na warstwę

| Warstwa | Konwencja |
|---|---|
| klucze JSON | `snake_case` **albo** `camelCase` — jedno, w całym API |
| nazwy schematów | `PascalCase` |
| `operationId` | `camelCase` |
| ścieżki | `kebab-case`, liczba mnoga, bez czasowników (`/orders`, nie `/getOrder`) |

Mieszanka (`firstname` obok `first_name` obok `firstName`) to podatek płacony
przy każdym mapowaniu.

### R7.5 YAML: kody statusu w cudzysłowie

```yaml
responses:
  200:      # ❌ YAML parsuje to jako liczbę
  "200":    # ✅
```

Część narzędzi to wybacza, część nie. Spectral zgłasza
`Mapping key must be a string scalar rather than number` — i słusznie, bo
w specyfikacji OpenAPI klucz `responses` jest stringiem.

### R7.6 Tagi

Każda operacja ma `tag`, każdy `tag` jest zadeklarowany w sekcji `tags`. Orval
generuje pliki i hooki per tag — bez tagów wszystko ląduje w jednym worku.

### R7.7 Wersja OpenAPI

Zadeklaruj `3.0.3` albo `3.1.0` i trzymaj się konsekwentnie (patrz R4.2).
`3.1` jest w pełni zgodne z JSON Schema, ale nie wszystkie generatory wspierają
je równie dobrze — sprawdź swój, zanim wybierzesz.

---

## 8. Anty-wzorce — ściąga

| Anty-wzorzec | Dlaczego boli | Zamiast tego |
|---|---|---|
| Kształt opisany w `description` | Generator tego nie czyta | `oneOf` / `enum` / `nullable` |
| Schemat inline | N kopii typu w kliencie | `$ref` do `components/schemas` |
| `type: string` dla pola z listą wartości | Front trzyma listę w komentarzu | `enum` |
| Brak `required` | Wszystko `?`, kod defensywny | jawne `required` |
| Brak `nullable` przy polu, które zwraca `null` | Crash na `.toUpperCase()` | `nullable: true` |
| `""` w jednym miejscu, `null` w drugim | Dwa warunki na to samo | jedna konwencja |
| Rozpoznawanie błędu po `message` | Psuje się przy i18n | `code` z `enum` |
| Tylko happy path w `responses` | Front nie obsłuży błędu, którego nie zna | wszystkie kody |
| `example` niezgodny z `schema` | Mock produkuje dane, które nie przejdą walidacji | walidacja przykładów w CI |
| Brak `operationId` | Losowe nazwy funkcji, zmieniają się przy zmianie ścieżki | jawny `operationId` |

---

## 9. Definition of Done — checklista do PR

Kontrakt jest gotowy do wydania frontowi, gdy **wszystkie** punkty są ✅:

**Struktura**
- [ ] Zero schematów inline w `responses` i `requestBody` — wszędzie `$ref`
- [ ] Powtarzalne parametry i nagłówki w `components/parameters`
- [ ] Powtarzalne odpowiedzi błędów w `components/responses`
- [ ] Nazwy schematów `PascalCase`, domenowe, bez `200`/`Dto`
- [ ] Brak nieużywanych schematów

**Typy**
- [ ] Każde pole o skończonym zbiorze wartości ma `enum`
- [ ] Każde pole daty ma `format: date-time` albo `date` (+ strefa w opisie)
- [ ] `format` dla `email`, `uri`, `uuid`
- [ ] Zakresy: `minimum`, `maxLength`, `pattern` tam, gdzie backend waliduje
- [ ] `integer` vs `number` świadomie

**Nullability**
- [ ] Każdy obiekt ma `required`
- [ ] `nullable: true` (3.0) / `type: [x, "null"]` (3.1) na wszystkim, co zwraca `null`
- [ ] Jedna konwencja `null` vs `""` w całym pliku
- [ ] Konwencja `nullable` zgodna z zadeklarowaną wersją OpenAPI

**Warianty**
- [ ] Każdy endpoint zwracający różne kształty ma `oneOf` + `discriminator`
- [ ] Wspólne części przez `allOf`, nie copy-paste
- [ ] Nic istotnego dla typu nie zostało wyłącznie w `description`

**Błędy**
- [ ] Wszystkie kody, jakie operacja może zwrócić
- [ ] Kilka kształtów pod jednym kodem → `oneOf` (albo jawnie: „mogą przyjść naraz")
- [ ] Błąd ma stabilny `code` z `enum`, nie tylko `message`

**Higiena**
- [ ] `operationId` na każdej operacji, `camelCase`
- [ ] `description` na każdej odpowiedzi, niepuste
- [ ] `example` przy każdym schemacie, zgodny ze schematem
- [ ] Tagi zadeklarowane i przypisane
- [ ] Kody statusu w YAML w cudzysłowie
- [ ] `spectral lint` przechodzi bez `error`

---

## 10. Automatyzacja — bo checklista sama się nie wyegzekwuje

Dokument, którego nikt nie sprawdza, jest ozdobą. Połowa powyższych reguł da się
zlintować maszynowo. W paczce jest gotowy ruleset: **`spectral.yaml`**.

```bash
npm i -D @stoplight/spectral-cli
cp spectral.yaml .spectral.yaml
npx spectral lint openapi.yaml
```

W CI (blokuje merge):

```bash
npx spectral lint openapi.yaml --fail-severity=error
```

Docelowo, gdy kontrakt jest czysty, podnieś poprzeczkę — wtedy każde nowe
ostrzeżenie blokuje merge:

```bash
npx spectral lint openapi.yaml --fail-severity=warn
```

Ruleset rozszerza wbudowany `spectral:oas` i dokłada reguły z tego dokumentu:
`response-schema-must-be-ref`, `request-body-schema-must-be-ref`,
`parameter-must-be-ref`, `enum-for-status-like-fields`, `date-time-needs-format`,
`object-must-declare-required`, `operation-must-document-4xx`,
`operation-operationid-camel-case`, `schema-name-pascal-case`,
`response-must-have-description`, `schema-should-have-example`.

**Strategia dla istniejącego API (brownfield):** nie da się naprawić 1000
ostrzeżeń w jednym PR i nie ma sensu próbować. Ustaw `--fail-severity=error`,
napraw najpierw `$ref` (to jedno da największy efekt w wygenerowanym kliencie),
potem `enum` i `nullable`. Nowe endpointy od razu muszą przechodzić pełny zestaw.

**Dalsze kroki, gdy lint już działa:**

- **Prism** — mock server z kontraktu. Front zaczyna pracę, zanim backend
  cokolwiek zaimplementuje. Wymusza też sensowne `example`.
- **Kontrola zmian łamiących** — `oasdiff` albo Optic w CI, żeby usunięcie pola
  z `required` nie przeszło niezauważone.
- **Testy kontraktowe** — walidacja rzeczywistych odpowiedzi backendu przeciw
  schematowi. To jedyna rzecz, która trwale gwarantuje, że kontrakt nie
  rozjeżdża się z implementacją.

---

## 11. Skąd to brać — źródła

Nie trzeba wymyślać standardu od zera, istnieją dojrzałe, publiczne.

**Style guide'y do skopiowania i przycięcia pod siebie**

- **[Zalando RESTful API Guidelines](https://opensource.zalando.com/restful-api-guidelines/)** — najczęściej cytowany otwarty przewodnik, ~150 ponumerowanych reguł MUST/SHOULD/MAY, licencja CC-BY. Najlepszy punkt startu: bierzesz, wycinasz 60% i masz własny standard. Osobne rozdziały o JSON, statusach i błędach.
- **[Google API Improvement Proposals](https://google.aip.dev/)** — bardziej opiniotwórcze, mocne w temacie nazewnictwa i ewolucji API.
- **[Microsoft REST API Guidelines](https://github.com/microsoft/api-guidelines)**
- **[API Stylebook](http://apistylebook.com/design/guidelines/)** — zbiorczy katalog guideline'ów różnych firm w jednym miejscu.

**Standardy**

- **[RFC 9457 — Problem Details for HTTP APIs](https://www.rfc-editor.org/rfc/rfc9457.html)** — standardowy kształt odpowiedzi błędu, `application/problem+json`. Zastępuje RFC 7807.
- **[Specyfikacja OpenAPI 3.1](https://spec.openapis.org/oas/latest.html)** — sekcje o `discriminator`, `oneOf` i `components`.
- **[JSON Schema](https://json-schema.org/understanding-json-schema/)** — bo schematy w OpenAPI 3.1 to JSON Schema; tu jest wyjaśnione `allOf`/`oneOf`/`anyOf`.

**Narzędzia**

- **[Spectral](https://github.com/stoplightio/spectral)** — linter, na którym oparty jest dołączony ruleset. Ma wtyczkę do VS Code, więc backend widzi błędy podczas pisania, a nie w code review.
- **[Redocly CLI](https://redocly.com/docs/cli/)** i **[vacuum](https://quobix.com/vacuum/)** — alternatywne lintery, vacuum jest znacznie szybszy na dużych plikach.
- **[Prism](https://github.com/stoplightio/prism)** — mock server z OpenAPI.
- **[oasdiff](https://github.com/Tufin/oasdiff)** — wykrywanie zmian łamiących.
- **[Orval](https://orval.dev/)** — dokumentacja jest wprost o tym, że jakość wygenerowanego klienta = jakość kontraktu.

---

## 12. Proces — bo to nie jest problem techniczny

Kontrakt pisany po implementacji jest dokumentacją. Kontrakt pisany przed
implementacją jest kontraktem. Różnica jest procesowa, nie techniczna.

Minimalny działający układ:

1. **Kontrakt powstaje przed kodem.** Backend proponuje, front recenzuje **zanim**
   zacznie się implementacja. Pół godziny review oszczędza tydzień przeróbek.
2. **Kontrakt żyje w repo i przechodzi przez PR.** Nie na Drive, nie w Postmanie.
   Zmiana kontraktu = PR z diffem, który widać.
3. **Lint blokuje merge.** Reguła, której nie sprawdza CI, nie istnieje.
4. **Jedno pytanie na review:** „czy front napisze z tego ekran bez pytania o
   cokolwiek?" Jeśli reviewer musi dopytać — to jest właśnie ta luka, którą trzeba
   zamknąć w kontrakcie, a nie w odpowiedzi na Slacku.

---

## Aneks — wynik lintu na trzech załączonych kontraktach

Dla kalibracji: ten sam ruleset (`spectral.yaml`) puszczony na trzech
kontraktach przekazanych przez backend. Liczby to liczba wystąpień.

| Reguła | `openapi.yaml` | `openapi_8_.yaml` | `openapi.json` |
|---|---:|---:|---:|
| `parser` (kody statusu jako liczby w YAML) | 396 | 396 | 0 |
| `parameter-must-be-ref` | 295 | 295 | 161 |
| `date-time-needs-format` | 154 | 150 | 157 |
| `response-must-have-description` | 93 | 93 | 98 |
| `response-schema-must-be-ref` | 92 | 92 | 157 |
| `oas3-valid-schema-example` (przykład ≠ schemat) | 220 | 24 | 0 |
| `operation-must-document-4xx` | 45 | 45 | 4 |
| `object-must-declare-required` | 33 | 38 | 15 |
| `request-body-schema-must-be-ref` | 18 | 18 | 0 |
| `enum-for-status-like-fields` | 15 | 12 | 75 |
| `operation-operationid-camel-case` | 0 | 0 | 96 |
| **Razem** | **1399** | **1206** | **911** |

Dwa obserwowalne wnioski, które dobrze ilustrują, po co jest ten dokument:

- W `openapi.yaml` / `openapi_8_.yaml` na **1057 schematów typu `string` tylko 11
  ma `enum`**, a znaczników `nullable` jest 129 przy 212 schematach obiektowych
  rozpisanych inline. Kształt `{ data, paginate, respond }` powtarza się
  **25 razy** jako niezależna definicja — stąd duplikaty w Orvalu.
- W `openapi.json` koperta `{ data, message, success }` jest zdefiniowana inline
  **64 razy**, a paginacja **12 razy**. To 76 typów w kliencie, które powinny być
  dwoma.

Różnica między `openapi.yaml` a `openapi_8_.yaml` (1399 → 1206) pokazuje, że
poprawki idą w dobrą stronę — spadły głównie niezgodne przykłady (220 → 24)
i doszły `enum`y oraz `nullable`. Reszta to nadal ten sam brak `$ref`.
