<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remembers in the session where a visitor came from, so an enquiry sent later
 * from the contact form can say which channel brought it. Session only: nothing
 * is written to a cookie of its own and nothing leaves the server.
 *
 * A visit tagged with utm_* (or a Google Ads gclid) replaces what is on file -
 * the latest campaign is the one that earned the enquiry. An untagged visit is
 * recorded only when nothing is on file yet, so moving between pages never
 * overwrites the original landing page and referrer.
 */
class RememberLeadSource
{
    public const SESSION_KEY = 'lead_source';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && ! $request->is('panel*', 'portal*', 'api/*', 'up')) {
            $tagged = $request->filled('utm_source') || $request->filled('gclid');

            if ($tagged || ! $request->session()->has(self::SESSION_KEY)) {
                $request->session()->put(self::SESSION_KEY, $this->describe($request));
            }
        }

        return $next($request);
    }

    /** @return array<string, string|null> */
    private function describe(Request $request): array
    {
        $source = $request->query('utm_source');
        $medium = $request->query('utm_medium');

        if (! filled($source) && $request->filled('gclid')) {
            [$source, $medium] = ['google', 'cpc'];
        }

        return [
            'utm_source' => $this->clip($source, 100),
            'utm_medium' => $this->clip($medium, 100),
            'utm_campaign' => $this->clip($request->query('utm_campaign'), 100),
            'referrer' => $this->externalReferrer($request),
            'landing_page' => $this->clip('/'.ltrim($request->path(), '/'), 255),
        ];
    }

    /** The referring host, or null for a direct visit or one from this site. */
    private function externalReferrer(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        if (! is_string($host) || $host === '' || strcasecmp($host, $request->getHost()) === 0) {
            return null;
        }

        return $this->clip(strtolower($host), 255);
    }

    private function clip(mixed $value, int $length): ?string
    {
        return is_string($value) && trim($value) !== '' ? mb_substr(trim($value), 0, $length) : null;
    }
}
