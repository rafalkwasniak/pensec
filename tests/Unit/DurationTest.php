<?php

namespace Tests\Unit;

use App\Support\Duration;
use PHPUnit\Framework\TestCase;

class DurationTest extends TestCase
{
    public function test_it_drops_the_unit_nobody_reads_at_that_scale(): void
    {
        $this->assertSame('0 min', Duration::compact(45));
        $this->assertSame('11 min', Duration::compact(11 * 60 + 22));
        $this->assertSame('2 h 45 min', Duration::compact(2 * 3600 + 45 * 60));
        $this->assertSame('3 d 21 h', Duration::compact(3 * 86400 + 21 * 3600 + 32 * 60));
    }
}
