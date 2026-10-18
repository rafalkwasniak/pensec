<?php

namespace Tests\Unit;

use App\Support\TsharkEndpoints;
use PHPUnit\Framework\TestCase;

class TsharkEndpointsTest extends TestCase
{
    public function test_multicast_and_broadcast_addresses_are_named(): void
    {
        $this->assertSame('multicast', TsharkEndpoints::groupLabel('224.0.0.251'));
        $this->assertSame('multicast', TsharkEndpoints::groupLabel('239.255.255.250'));
        $this->assertSame('rozgłoszeniowy', TsharkEndpoints::groupLabel('255.255.255.255'));
        $this->assertSame('multicast', TsharkEndpoints::groupLabel('ff02::fb'));
    }

    public function test_ordinary_hosts_are_not_labelled(): void
    {
        $this->assertNull(TsharkEndpoints::groupLabel('192.168.0.1'));
        $this->assertNull(TsharkEndpoints::groupLabel('192.168.0.255'));
        $this->assertNull(TsharkEndpoints::groupLabel('10.0.0.5'));
        $this->assertNull(TsharkEndpoints::groupLabel('fe80::1'));
        $this->assertNull(TsharkEndpoints::groupLabel('223.0.0.1'));
    }
}
