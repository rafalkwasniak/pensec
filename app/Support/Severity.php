<?php

namespace App\Support;

/**
 * How badly something matters, decided in code.
 *
 * Until now a report said only "wymagające uwagi" or "czysto", which leaves the
 * reader to work out what to do first. Ranking is a judgement, so it is made
 * here from the evidence rather than by the model - the same rule as every
 * other figure in the document.
 *
 * The grading is deliberately cautious. A check that could not confirm its own
 * finding lands one step below one that did, and a test that never completed is
 * not a finding at all: it belongs to the gaps, which are reported separately.
 * Overstating severity in a security report costs trust exactly once.
 */
class Severity
{
    public const CRITICAL = 'critical';

    public const HIGH = 'high';

    public const MEDIUM = 'medium';

    public const INFO = 'info';

    /** Most severe first; also the order findings are listed in. */
    public const ORDER = [self::CRITICAL, self::HIGH, self::MEDIUM, self::INFO];

    private const LABELS = [
        self::CRITICAL => 'Krytyczne',
        self::HIGH => 'Wysokie',
        self::MEDIUM => 'Średnie',
        self::INFO => 'Informacyjne',
    ];

    /**
     * Services whose presence on an internal network is worth a line of its own.
     * An open port is an observation, not a vulnerability, so nothing here is
     * graded critical - these say "this is reachable", not "this is broken".
     *
     * @var array<int, array{level: string, name: string, why: string}>
     */
    private const EXPOSED_SERVICES = [
        23 => ['level' => self::HIGH, 'name' => 'Telnet', 'why' => 'Protokół przesyła hasła otwartym tekstem.'],
        21 => ['level' => self::HIGH, 'name' => 'FTP', 'why' => 'Protokół przesyła hasła otwartym tekstem.'],
        3389 => ['level' => self::HIGH, 'name' => 'Pulpit zdalny (RDP)', 'why' => 'Typowa droga rozprzestrzeniania się w sieci po przejęciu jednego konta.'],
        5900 => ['level' => self::HIGH, 'name' => 'VNC', 'why' => 'Zdalny pulpit, często bez wymuszonego uwierzytelnienia.'],
        445 => ['level' => self::MEDIUM, 'name' => 'Udostępnianie plików SMB', 'why' => 'Dostęp do plików i klasyczny cel ataków w sieci lokalnej.'],
        161 => ['level' => self::MEDIUM, 'name' => 'SNMP', 'why' => 'Ujawnia konfigurację urządzenia, często z domyślną nazwą wspólnoty.'],
        3306 => ['level' => self::MEDIUM, 'name' => 'Baza danych MySQL', 'why' => 'Baza danych nasłuchująca w sieci.'],
        5432 => ['level' => self::MEDIUM, 'name' => 'Baza danych PostgreSQL', 'why' => 'Baza danych nasłuchująca w sieci.'],
        1433 => ['level' => self::MEDIUM, 'name' => 'Baza danych MS SQL', 'why' => 'Baza danych nasłuchująca w sieci.'],
        6379 => ['level' => self::MEDIUM, 'name' => 'Redis', 'why' => 'Domyślnie bez uwierzytelnienia.'],
        27017 => ['level' => self::MEDIUM, 'name' => 'MongoDB', 'why' => 'Domyślnie bez uwierzytelnienia.'],
        389 => ['level' => self::MEDIUM, 'name' => 'LDAP', 'why' => 'Katalog firmowy dostępny w sieci.'],
    ];

    public static function label(string $level): string
    {
        return self::LABELS[$level] ?? self::LABELS[self::INFO];
    }

    public static function rank(string $level): int
    {
        $position = array_search($level, self::ORDER, true);

        return $position === false ? count(self::ORDER) : $position;
    }

    /** True for anything somebody has to act on. */
    public static function actionable(string $level): bool
    {
        return $level !== self::INFO;
    }

    /**
     * Grades an NSE finding from what the script actually said.
     *
     * nmap is explicit about its own confidence and that is respected here:
     * `State: UNKNOWN (unable to test)` outranks the word VULNERABLE printed
     * above it, because the script announced the vulnerability it looks for and
     * then failed to confirm it.
     *
     * @return array{level: string, confirmed: bool, inconclusive: bool}
     */
    public static function ofScript(string $output): array
    {
        $text = mb_strtolower($output);
        $hasCve = (bool) preg_match('/CVE-\d{4}-\d{4,7}/i', $output);

        // The script never produced a verdict - it timed out, got no answer, or
        // fell over. None of those is a finding; they belong to the gaps.
        if (str_contains($text, 'timeout')
            || str_contains($text, 'no reply from server')
            || str_contains($text, 'script execution failed')
            || str_starts_with($text, 'error:')) {
            return ['level' => self::INFO, 'confirmed' => false, 'inconclusive' => true];
        }

        if (str_contains($text, 'unable to test') || str_contains($text, 'state: unknown')) {
            return ['level' => self::MEDIUM, 'confirmed' => false, 'inconclusive' => true];
        }

        if (str_contains($text, 'state: vulnerable')) {
            return ['level' => $hasCve ? self::CRITICAL : self::HIGH, 'confirmed' => true, 'inconclusive' => false];
        }

        if (str_contains($text, 'state: likely vulnerable') || str_contains($text, 'vulnerable:')) {
            return ['level' => self::HIGH, 'confirmed' => false, 'inconclusive' => false];
        }

        if ($hasCve) {
            return ['level' => self::HIGH, 'confirmed' => false, 'inconclusive' => false];
        }

        return ['level' => self::INFO, 'confirmed' => false, 'inconclusive' => false];
    }

    /**
     * @return array{level: string, name: string, why: string}|null
     */
    public static function ofPort(int $port): ?array
    {
        return self::EXPOSED_SERVICES[$port] ?? null;
    }

    /** Maps a severity word the probe supplied onto our own scale. */
    public static function ofProbeWord(?string $word): string
    {
        return match (mb_strtoupper((string) $word)) {
            'CRITICAL' => self::CRITICAL,
            'HIGH' => self::HIGH,
            'MEDIUM', 'WARNING', 'MODERATE' => self::MEDIUM,
            default => self::INFO,
        };
    }

    /**
     * The headline of an NSE finding. nmap prints a bare "VULNERABLE:" marker
     * before the name, so the first meaningful line is the one worth showing.
     */
    public static function titleOf(string $output, string $fallback): string
    {
        foreach (preg_split('/\R/', $output) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || preg_match('/^(likely\s+)?(not\s+)?vulnerable:?$/i', $line)) {
                continue;
            }

            return mb_strimwidth($line, 0, 160, '…');
        }

        return $fallback;
    }

    /**
     * @return list<string>
     */
    public static function cvesIn(string $output): array
    {
        preg_match_all('/CVE-\d{4}-\d{4,7}/i', $output, $matches);

        return array_values(array_unique(array_map(mb_strtoupper(...), $matches[0])));
    }
}
