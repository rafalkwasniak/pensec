<?php

namespace Tests\Unit;

use App\Support\ScanTime;
use Tests\TestCase;

/**
 * The probe writes scan_time with no zone in it. Reading it wrongly does not
 * fail loudly - it produces a delay that looks like a measurement and is off
 * by two hours, which is exactly what this column exists to settle. Boots the
 * framework because the assumed zone is configuration, not a constant.
 */
class ScanTimeTest extends TestCase
{
    public function test_it_reads_the_format_the_firmware_writes_and_moves_it_to_utc(): void
    {
        $parsed = ScanTime::parse('2026-08-16 13:38:12');

        $this->assertNotNull($parsed);
        $this->assertSame('2026-08-16 11:38:12', $parsed->toDateTimeString());
        $this->assertSame('UTC', $parsed->timezoneName);
    }

    public function test_it_reads_an_iso_string_with_and_without_an_offset(): void
    {
        $this->assertSame('2026-08-16 11:38:12', ScanTime::parse('2026-08-16T13:38:12')?->toDateTimeString());
        $this->assertSame('2026-08-16 11:38:12', ScanTime::parse('2026-08-16T13:38:12+02:00')?->toDateTimeString());
    }

    public function test_a_missing_or_unreadable_value_yields_nothing(): void
    {
        $this->assertNull(ScanTime::parse(null));
        $this->assertNull(ScanTime::parse(''));
        $this->assertNull(ScanTime::parse('   '));
        $this->assertNull(ScanTime::parse('wczoraj'));
        $this->assertNull(ScanTime::parse(1755344292));
        $this->assertNull(ScanTime::parse(['2026-08-16 13:38:12']));
    }

    public function test_a_clock_that_never_reached_the_network_is_not_a_measurement(): void
    {
        // A Raspberry Pi with no time source starts here, and every report it
        // sends would otherwise claim a delay measured in decades.
        $this->assertNull(ScanTime::parse('1970-01-01 01:00:00'));
    }

    public function test_a_scan_cannot_have_run_tomorrow(): void
    {
        $this->assertNull(ScanTime::parse(now()->addWeek()->format('Y-m-d H:i:s')));
    }
}
