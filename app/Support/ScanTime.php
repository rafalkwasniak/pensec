<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Throwable;

class ScanTime
{
    /**
     * The formats a scan_time is accepted in. The first is what the firmware
     * writes today; the rest cost nothing and spare us a silent blank column
     * if it ever starts writing an ISO string. Nothing else is guessed at: a
     * lenient parser turns a stray number into a date, and a wrong date reads
     * as a real measurement.
     *
     * @var list<string>
     */
    private const FORMATS = ['!Y-m-d H:i:s', '!Y-m-d\TH:i:s', '!Y-m-d\TH:i:sP', '!Y-m-d H:i'];

    /**
     * When a scan ran, taken from the document and moved to UTC so it can be
     * compared with received_at. Null whenever the probe left the field out or
     * wrote something that is not a date - the panel says so rather than
     * showing a number nobody can stand behind.
     */
    public static function parse(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $zone = config('pensec.reports.probe_timezone');

        foreach (self::FORMATS as $format) {
            try {
                $parsed = CarbonImmutable::createFromFormat($format, trim($value), $zone);
            } catch (Throwable) {
                continue;
            }

            if ($parsed === false) {
                continue;
            }

            // A Raspberry Pi has no clock of its own: without the network it
            // starts in 1970, and a scan cannot have run tomorrow. Neither is
            // a measurement, and both would make the delay column nonsense.
            if ($parsed->year < 2020 || $parsed->isAfter(CarbonImmutable::now()->addDay())) {
                return null;
            }

            return $parsed->utc();
        }

        return null;
    }
}
