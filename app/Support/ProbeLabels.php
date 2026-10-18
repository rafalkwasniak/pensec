<?php

namespace App\Support;

/**
 * Turns the probe's raw status tokens into plain Polish for the client report.
 *
 * The probe speaks in short English/technical tokens - "blocked",
 * "foreign_block", "LIKELY VULNERABLE", "State: UNKNOWN", "WARNING". A network
 * administrator reads them fine, so the expert report keeps them verbatim. The
 * manager report is read by someone non-technical, and a bare "foreign_block"
 * tells them nothing. These helpers map only the tokens we know; anything else
 * (an address, a number, a version) passes through unchanged, so nothing real
 * is ever hidden or guessed.
 */
class ProbeLabels
{
    /** Exact-match tokens. A value equal to a key (trimmed) is replaced whole. */
    private const TERMS = [
        // Result / coverage states.
        'ok' => 'prawidłowy',
        'blocked' => 'zablokowany',
        'foreign_block' => 'zablokowane przez zakres lub politykę badania',
        'disconnected_during_audit' => 'rozłączono w trakcie badania',
        'timeout' => 'przekroczono czas',
        'error' => 'błąd',
        'failed' => 'nie powiodło się',
        'running' => 'w toku, nie ukończono',
        'SECURE' => 'bezpieczny',
        // Port states.
        'open' => 'otwarty',
        'closed' => 'zamknięty',
        'filtered' => 'filtrowany',
        'open|filtered' => 'otwarty lub filtrowany',
        // Probe verdict words.
        'WARNING' => 'wymaga uwagi',
        'INCONCLUSIVE' => 'nierozstrzygnięte',
        // Field labels the probe leaves in English.
        'Status' => 'Stan',
        'Reason' => 'Powód',
        'Assessment' => 'Ocena',
    ];

    /**
     * Friendly Polish names for the probe's NSE/tool script ids, for the TEST
     * column of the manager report. The expert report keeps the raw ids.
     *
     * @var array<string, string>
     */
    private const TESTS = [
        'vulners' => 'Dopasowanie znanych podatności',
        'http-slowloris-check' => 'Odporność na przeciążenie (Slowloris)',
        'http-phpmyadmin-dir-traversal' => 'phpMyAdmin (odczyt plików)',
        'http-litespeed-sourcecode-download' => 'Ujawnienie kodu źródłowego',
        'ssl-poodle' => 'Szyfrowanie SSL (POODLE)',
        'ssl-enum-ciphers' => 'Przegląd szyfrów TLS',
        'ssl-cert' => 'Certyfikat TLS',
        'ssl-dh-params' => 'Parametry wymiany kluczy TLS',
        'snmp-info' => 'Konfiguracja SNMP',
        'snmp-sysdescr' => 'Opis systemu (SNMP)',
        'nbstat' => 'Nazwa NetBIOS',
        'upnp-info' => 'Usługa UPnP',
        'dns-service-discovery' => 'Rozgłaszanie usług (mDNS)',
        'redis-info' => 'Baza Redis (metadane)',
        'memcached-info' => 'Baza Memcached (metadane)',
        'mongodb-info' => 'Baza MongoDB (metadane)',
        'smb-enum-shares' => 'Udziały SMB',
        'smb-os-discovery' => 'Rozpoznanie systemu przez SMB',
        'ldap-search' => 'Katalog LDAP',
        's7-info' => 'Sterownik Siemens S7',
        'modbus-discover' => 'Protokół Modbus',
    ];

    /** Longest first, so "State: LIKELY VULNERABLE" wins over "VULNERABLE". */
    private const EVIDENCE = [
        'State: LIKELY VULNERABLE' => 'Stan: prawdopodobnie podatny',
        'State: NOT VULNERABLE' => 'Stan: niepodatny',
        'State: VULNERABLE' => 'Stan: podatny',
        'State: UNKNOWN' => 'Stan: niepotwierdzony',
        'LIKELY VULNERABLE' => 'prawdopodobnie podatny',
        'NOT VULNERABLE' => 'niepodatny',
        'VULNERABLE:' => 'PODATNOŚĆ:',
        'IDs:' => 'Identyfikatory CVE:',
    ];

    /** A probe test id to a friendly Polish name, or unchanged when unknown. */
    public static function testName(string $name): string
    {
        return self::TESTS[$name] ?? $name;
    }

    /**
     * One token to plain Polish, or unchanged when we do not know it.
     */
    public static function term(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return self::TERMS[trim($value)] ?? $value;
    }

    /**
     * Humanise the status words inside a block of raw nmap/NSE evidence. Only
     * the known phrases are touched; the rest of the evidence stays verbatim.
     */
    public static function evidence(string $text): string
    {
        return strtr($text, self::EVIDENCE);
    }
}
