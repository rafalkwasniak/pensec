<?php

namespace App\Support;

/**
 * Turns the diagnostics block into something a person can read.
 *
 * The probe sends these as small objects of technical flags - things like
 * `{"gratuitous_arp_blocked": false}`. Printed as key and value that says
 * nothing at all, and to a non-technical reader it says less than nothing: a
 * bare `false` looks like a failure when it may be the good outcome, and here
 * it is the bad one. So every known flag carries the sentence that states what
 * it actually means, in both directions, plus which way round is a concern.
 *
 * Unknown keys and unknown fields still render, using a humanised version of
 * the raw name. A new test on the probe therefore shows up in the report as
 * soon as it starts sending, rather than silently vanishing from it - the same
 * rule the rest of the document follows.
 */
class Diagnostics
{
    /**
     * key => [label, fields]. A field is either a plain measurement
     * (['label', 'unit']) or a flag whose two readings are spelled out.
     *
     * @var array<string, array{label: string, fields?: array<string, array<string, mixed>>}>
     */
    private const KNOWN = [
        'latency' => [
            'label' => 'Opóźnienia w sieci',
            'fields' => [
                'min_ms' => ['label' => 'Najniższe', 'unit' => 'ms'],
                'avg_ms' => ['label' => 'Średnie', 'unit' => 'ms'],
                'max_ms' => ['label' => 'Najwyższe', 'unit' => 'ms'],
            ],
        ],
        'dns_health' => [
            'label' => 'Kondycja DNS',
            'fields' => [
                'server' => ['label' => 'Odpytany serwer'],
                'query_time_ms' => ['label' => 'Czas odpowiedzi', 'unit' => 'ms'],
            ],
        ],
        'mitm_vulnerability' => [
            'label' => 'Podatność na podsłuch (MITM)',
            'fields' => [
                'gratuitous_arp_blocked' => [
                    'true' => 'Ruch Gratuitous ARP jest blokowany.',
                    'false' => 'Brak blokady Gratuitous ARP — ruch w sieci można przechwycić i podsłuchać.',
                    'concern' => false,
                    'level' => Severity::HIGH,
                ],
            ],
        ],
        'wpad_vulnerability' => [
            'label' => 'Podatność WPAD',
            'fields' => [
                'wpad_authoritative_dns' => [
                    'true' => 'W DNS istnieje autorytatywny wpis WPAD.',
                    'false' => 'Brak autorytatywnego wpisu WPAD w DNS — można podszyć się pod serwer konfiguracji proxy.',
                    'concern' => false,
                    'level' => Severity::HIGH,
                ],
            ],
        ],
        'ipv6_spoofing' => [
            'label' => 'Podszywanie się w IPv6',
            'fields' => [
                'ipv6_spoofing_vulnerable' => [
                    'true' => 'Sieć jest podatna na podszywanie się pod router IPv6.',
                    'false' => 'Nie stwierdzono podatności na podszywanie się w IPv6.',
                    'concern' => true,
                    'level' => Severity::HIGH,
                ],
            ],
        ],
        'rogue_dhcp' => [
            'label' => 'Obcy serwer DHCP',
            'fields' => [
                'rogue_dhcp_detected' => [
                    'true' => 'Wykryto nieuprawniony serwer DHCP.',
                    'false' => 'Nie wykryto nieuprawnionego serwera DHCP.',
                    'concern' => true,
                    'level' => Severity::HIGH,
                ],
            ],
        ],
        'physical_port_security' => [
            'label' => 'Zabezpieczenie portu dostępowego',
            'fields' => [
                'dtp_trunking_active' => [
                    'true' => 'Port negocjuje trunk (DTP) — można z niego wejść do innych sieci VLAN.',
                    'false' => 'Port nie negocjuje trunku.',
                    'concern' => true,
                    'level' => Severity::HIGH,
                ],
                'stp_bpdu_guard_missing' => [
                    'true' => 'Brak BPDU Guard — podłączony przełącznik może przejąć rolę głównego w topologii.',
                    'false' => 'BPDU Guard jest włączony.',
                    'concern' => true,
                    'level' => Severity::MEDIUM,
                ],
            ],
        ],
        'vlan_hopping' => ['label' => 'Przeskakiwanie między VLAN-ami'],
        'network_fabric' => ['label' => 'Konfiguracja portu dostępowego'],
        'egress_filtering' => [
            'label' => 'Filtrowanie ruchu wychodzącego',
            'fields' => [
                'allowed_ports' => [
                    'label' => 'Porty przepuszczane na zewnątrz',
                    'empty' => 'Żaden z badanych portów nie jest przepuszczany.',
                    'concern_when_filled' => true,
                    'level' => Severity::MEDIUM,
                ],
            ],
        ],
        'wireless_security' => [
            'label' => 'Bezpieczeństwo sieci bezprzewodowej',
            'fields' => [
                'monitor_mode_enabled' => [
                    'true' => 'Nasłuch w trybie monitora działał.',
                    'false' => 'Nie udało się włączyć trybu monitora, więc nasłuch się nie odbył.',
                    'concern' => false,
                    // This reading says the test did not happen, so it is a hole
                    // in the coverage rather than something found in the network.
                    'gap' => true,
                ],
                'eapol_frames_observed' => ['label' => 'Przechwycone ramki EAPOL'],
            ],
        ],
        'top_talkers' => ['label' => 'Najbardziej obciążające urządzenia'],
        'bandwidth' => ['label' => 'Przepustowość łącza'],
    ];

    public static function label(string $key): string
    {
        return self::KNOWN[$key]['label'] ?? self::humanise($key);
    }

    /**
     * One diagnostic, ready to render.
     *
     * `kind` decides the shape: `talkers` is a traffic table, `fields` a list of
     * readings, `text` a paragraph the probe wrote itself. `error` is set when
     * the test reported that it could not run - which is never the same thing
     * as a test that ran and found nothing.
     *
     * @return array{key: string, label: string, kind: string, rows: list<array<string, mixed>>, fields: list<array{label: ?string, value: string, concern: bool, level: string, gap: bool}>, text: string, error: ?string}
     */
    public static function describe(string $key, mixed $value): array
    {
        $entry = [
            'key' => $key,
            'label' => self::label($key),
            'kind' => 'text',
            'rows' => [],
            'fields' => [],
            'text' => '',
            'error' => null,
        ];

        if ($key === 'top_talkers' && is_string($value)) {
            $entry['kind'] = 'talkers';
            $entry['rows'] = TsharkEndpoints::parse($value);

            // Unparseable output still has to reach the page as something.
            if ($entry['rows'] === []) {
                $entry['kind'] = 'text';
                $entry['text'] = trim($value);
            }

            return $entry;
        }

        if (is_string($value)) {
            // Some probe-side tests write their result as "Label: value" lines
            // rather than a sentence. Two or more of them is a reading, not
            // prose, so it renders as one - a lone line stays a sentence.
            $lines = self::labelledLines($value);

            if (count($lines) >= 2) {
                $entry['kind'] = 'fields';
                $entry['fields'] = $lines;

                return $entry;
            }

            $entry['text'] = trim($value);

            return $entry;
        }

        if (! is_array($value)) {
            $entry['text'] = is_scalar($value) ? (string) $value : '';

            return $entry;
        }

        if (array_is_list($value)) {
            $entry['fields'] = self::listFields($value);
            $entry['kind'] = $entry['fields'] === [] ? 'text' : 'fields';

            return $entry;
        }

        $entry['kind'] = 'fields';

        foreach ($value as $field => $raw) {
            if ($field === 'error') {
                $entry['error'] = is_string($raw) && $raw !== '' ? $raw : 'Test zgłosił błąd.';

                continue;
            }

            $described = self::field($key, (string) $field, $raw);

            if ($described !== null) {
                $entry['fields'][] = $described;
            }
        }

        if ($entry['fields'] === []) {
            $entry['kind'] = 'text';
        }

        return $entry;
    }

    /**
     * @return array{label: ?string, value: string, concern: bool, level: string, gap: bool}|null
     */
    private static function field(string $key, string $field, mixed $raw): ?array
    {
        $spec = self::KNOWN[$key]['fields'][$field] ?? [];

        if (is_bool($raw) && isset($spec['true'], $spec['false'])) {
            $concern = $raw === ($spec['concern'] ?? true);

            return [
                'label' => null,
                'value' => $raw ? $spec['true'] : $spec['false'],
                'concern' => $concern,
                // Only a concern carries weight; the reassuring reading is not
                // a finding and must not turn up in the ranked list.
                'level' => $concern ? ($spec['level'] ?? Severity::MEDIUM) : Severity::INFO,
                'gap' => $concern && ($spec['gap'] ?? false),
            ];
        }

        if (is_array($raw)) {
            $items = array_filter($raw, is_scalar(...));

            if ($items === []) {
                return [
                    'label' => $spec['label'] ?? self::humanise($field),
                    'value' => $spec['empty'] ?? 'brak',
                    'concern' => false,
                    'level' => Severity::INFO,
                    'gap' => false,
                ];
            }

            $concern = (bool) ($spec['concern_when_filled'] ?? false);

            return [
                'label' => $spec['label'] ?? self::humanise($field),
                'value' => implode(', ', array_map(strval(...), $items)),
                'concern' => $concern,
                'level' => $concern ? ($spec['level'] ?? Severity::MEDIUM) : Severity::INFO,
                'gap' => false,
            ];
        }

        if ($raw === null) {
            return null;
        }

        if (is_bool($raw)) {
            return [
                'label' => $spec['label'] ?? self::humanise($field),
                'value' => $raw ? 'tak' : 'nie',
                'concern' => false,
                'level' => Severity::INFO,
                'gap' => false,
            ];
        }

        if (! is_scalar($raw)) {
            return null;
        }

        $unit = isset($spec['unit']) ? ' '.$spec['unit'] : '';

        return [
            'label' => $spec['label'] ?? self::humanise($field),
            'value' => $raw.$unit,
            'concern' => false,
            'level' => Severity::INFO,
            'gap' => false,
        ];
    }

    /**
     * A diagnostic that arrives as a list of objects, such as the per-interface
     * VLAN checks.
     *
     * @param  list<mixed>  $value
     * @return list<array{label: ?string, value: string, concern: bool, level: string, gap: bool}>
     */
    private static function listFields(array $value): array
    {
        $fields = [];

        foreach ($value as $entry) {
            if (is_scalar($entry)) {
                $fields[] = ['label' => null, 'value' => (string) $entry, 'concern' => false, 'level' => Severity::INFO, 'gap' => false];

                continue;
            }

            if (! is_array($entry)) {
                continue;
            }

            $parts = [];

            foreach ($entry as $field => $raw) {
                if (is_scalar($raw) && (string) $raw !== '') {
                    $parts[] = self::humanise((string) $field).': '.$raw;
                }
            }

            if ($parts !== []) {
                $severity = mb_strtoupper((string) ($entry['severity'] ?? ''));

                $concern = ! in_array($severity, ['', 'INFO', 'SECURE'], true);

                $fields[] = [
                    'label' => null,
                    'value' => implode(', ', $parts),
                    'concern' => $concern,
                    'level' => $concern ? Severity::ofProbeWord($severity) : Severity::INFO,
                    'gap' => false,
                ];
            }
        }

        return $fields;
    }

    /**
     * Splits "Ping: 25.487 ms" style output into readings. Every non-empty line
     * has to match, otherwise the block is prose that merely contains a colon
     * and gets left alone.
     *
     * @return list<array{label: ?string, value: string, concern: bool, level: string, gap: bool}>
     */
    private static function labelledLines(string $value): array
    {
        $fields = [];

        foreach (preg_split('/\R/', trim($value)) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }

            if (! preg_match('/^([\p{L} ]{2,30}):\s*(.+)$/u', trim($line), $m)) {
                return [];
            }

            $fields[] = ['label' => trim($m[1]), 'value' => trim($m[2]), 'concern' => false, 'level' => Severity::INFO, 'gap' => false];
        }

        return $fields;
    }

    /** `stp_bpdu_guard_missing` reads better than nothing at all. */
    private static function humanise(string $name): string
    {
        return ucfirst(str_replace('_', ' ', $name));
    }
}
