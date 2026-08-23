<?php

namespace App\Support;

/**
 * Reads tshark's endpoint statistics back into rows.
 *
 * The probe stores what tshark printed - a fixed-width console table wrapped in
 * rules of equals signs, with a header line whose pipes do not line up with the
 * data underneath. Dropped into a PDF cell verbatim it reads as noise, so the
 * numbers are parsed out here and rendered as an ordinary table.
 *
 * The shape being parsed:
 *
 *     ================================================================================
 *     IPv4 Endpoints
 *     Filter:<No Filter>
 *      | Packets | | Bytes | | Tx Packets | | Tx Bytes | | Rx Packets | | Rx Bytes |
 *     224.0.0.251 5 1414 0 0 5 1414
 *     ================================================================================
 */
class TsharkEndpoints
{
    /**
     * @return list<array{address: string, packets: int, bytes: int, tx_packets: int, tx_bytes: int, rx_packets: int, rx_bytes: int}>
     */
    public static function parse(string $output): array
    {
        preg_match_all(
            '/^\s*(\S+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s*$/m',
            $output,
            $matches,
            PREG_SET_ORDER,
        );

        $rows = array_map(fn (array $m): array => [
            'address' => $m[1],
            'packets' => (int) $m[2],
            'bytes' => (int) $m[3],
            'tx_packets' => (int) $m[4],
            'tx_bytes' => (int) $m[5],
            'rx_packets' => (int) $m[6],
            'rx_bytes' => (int) $m[7],
        ], $matches);

        // Busiest first: that is the question the section is answering.
        usort($rows, fn (array $a, array $b): int => $b['bytes'] <=> $a['bytes']);

        return $rows;
    }

    /** Bytes as a person reads them. */
    public static function bytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1, ',', ' ').' kB';
        }

        return number_format($bytes / 1024 / 1024, 1, ',', ' ').' MB';
    }
}
