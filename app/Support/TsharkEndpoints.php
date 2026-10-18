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

    /**
     * A group address is not a device. tshark lists mDNS, SSDP, LLMNR and the
     * broadcast address among the endpoints, and sorted by traffic they sit high
     * in the table reading as hosts. This names them so a reader is not misled;
     * the row stays, because the traffic it carries is real. Returns null for an
     * ordinary unicast address, which is the common case.
     */
    public static function groupLabel(string $address): ?string
    {
        // Limited broadcast - the one broadcast address certain without a mask.
        if ($address === '255.255.255.255') {
            return 'rozgłoszeniowy';
        }

        // IPv4 multicast is 224.0.0.0/4: the first octet from 224 to 239.
        if (preg_match('/^(\d{1,3})\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $address, $m) === 1) {
            $first = (int) $m[1];

            return $first >= 224 && $first <= 239 ? 'multicast' : null;
        }

        // IPv6 multicast is ff00::/8 - any address whose first byte is 0xff.
        if (str_contains($address, ':') && preg_match('/^ff[0-9a-f]{0,2}:/i', $address) === 1) {
            return 'multicast';
        }

        return null;
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
