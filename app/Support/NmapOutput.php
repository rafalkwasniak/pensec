<?php

namespace App\Support;

/**
 * Reads nmap's normal output back into structure.
 *
 * The probe stores what nmap printed, verbatim, which is the right call - it
 * keeps the evidence intact. But a PDF cannot show a wall of console text, and
 * a language model must not be the thing that decides which ports were open.
 * So the tables in the document are built from here, by parsing, and the model
 * only ever writes prose around numbers this class produced.
 *
 * Everything is best-effort: unparseable output yields empty arrays rather than
 * an exception, because a report must still render when one host scanned oddly.
 */
class NmapOutput
{
    /**
     * The `PORT STATE SERVICE VERSION` table.
     *
     * @return list<array{port: int, transport: string, state: string, service: string, version: ?string}>
     */
    public static function ports(string $output): array
    {
        preg_match_all(
            '/^(\d{1,5})\/(tcp|udp)\s+(\S+)\s+(\S+)(?:[ \t]+(.+?))?[ \t]*$/mi',
            $output,
            $matches,
            PREG_SET_ORDER,
        );

        return array_map(fn (array $m): array => [
            'port' => (int) $m[1],
            'transport' => strtolower($m[2]),
            'state' => strtolower($m[3]),
            'service' => $m[4],
            'version' => isset($m[5]) && $m[5] !== '' ? trim($m[5]) : null,
        ], $matches);
    }

    /**
     * The same table narrowed to what is actually reachable. `open|filtered`
     * counts here too - nmap could not rule it out, so neither do we.
     *
     * @return list<array{port: int, transport: string, state: string, service: string, version: ?string}>
     */
    public static function openPorts(string $output): array
    {
        return array_values(array_filter(
            self::ports($output),
            fn (array $port): bool => str_starts_with($port['state'], 'open'),
        ));
    }

    /**
     * @return array{mac: string, vendor: ?string}|null
     */
    public static function macAddress(string $output): ?array
    {
        if (! preg_match('/^MAC Address:\s+([0-9A-F:]{17})(?:\s+\((.+?)\))?/mi', $output, $m)) {
            return null;
        }

        return [
            'mac' => strtoupper($m[1]),
            'vendor' => isset($m[2]) && $m[2] !== 'Unknown' ? $m[2] : null,
        ];
    }

    /**
     * NSE script output - the `| script-name:` blocks. These carry the actual
     * findings of a deep scan, so they are kept whole rather than summarised.
     *
     * @return list<array{name: string, output: string}>
     */
    public static function scripts(string $output): array
    {
        $scripts = [];
        $current = null;

        foreach (preg_split('/\R/', $output) ?: [] as $line) {
            if (! str_starts_with($line, '|')) {
                $current = null;

                continue;
            }

            // `| name: rest` or `|_name: rest` opens a block; anything else
            // starting with a pipe continues the one before it.
            if (preg_match('/^\|_?\s?([a-z0-9][a-z0-9._-]*):[ \t]*(.*)$/i', $line, $m)) {
                $scripts[] = ['name' => $m[1], 'output' => trim($m[2])];
                $current = array_key_last($scripts);

                continue;
            }

            if ($current === null) {
                continue;
            }

            $continuation = trim(preg_replace('/^\|_?\s?/', '', $line) ?? '');

            if ($continuation !== '') {
                $scripts[$current]['output'] = trim($scripts[$current]['output']."\n".$continuation);
            }
        }

        return array_values(array_filter(
            $scripts,
            fn (array $script): bool => $script['output'] !== '',
        ));
    }

    /**
     * True when the scan reached the host at all. Distinguishes "nothing was
     * open" from "we never got a reply", which read very differently.
     */
    public static function hostIsUp(string $output): bool
    {
        return (bool) preg_match('/^Host is up/mi', $output);
    }
}
