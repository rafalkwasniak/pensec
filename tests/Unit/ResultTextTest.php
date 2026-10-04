<?php

namespace Tests\Unit;

use App\Support\Polish;
use App\Support\ResultText;
use PHPUnit\Framework\TestCase;

class ResultTextTest extends TestCase
{
    public function test_polish_numbers_agree_with_their_noun(): void
    {
        $ports = fn (int $n): string => Polish::count($n, 'port', 'porty', 'portów');

        $this->assertSame(
            ['0 portów', '1 port', '2 porty', '4 porty', '5 portów', '12 portów', '14 portów', '22 porty', '112 portów', '124 porty'],
            array_map($ports, [0, 1, 2, 4, 5, 12, 14, 22, 112, 124]),
        );
    }

    public function test_a_result_that_says_what_happened_is_shown_as_that_sentence(): void
    {
        $this->assertSame(
            '192.168.0.94: Nie można ocenić modułu smb: skanowanie portów przekroczyło limit czasu.',
            ResultText::describe([
                'status' => 'timeout', 'test' => 'smb', 'enabled' => true, 'ip' => '192.168.0.94',
                'reason' => 'Nie można ocenić modułu smb: skanowanie portów przekroczyło limit czasu.',
            ]),
        );
    }

    public function test_folded_nuclei_matches_read_as_a_sentence(): void
    {
        $this->assertSame(
            'Secrets Patterns (PII) na 192.168.0.2: 2 trafienia (szablon secrets-patterns-pii)',
            ResultText::describe(['template' => 'secrets-patterns-pii', 'name' => 'Secrets Patterns (PII)', 'severity' => 'info', 'ip' => '192.168.0.2', 'matches' => 2]),
        );
    }

    public function test_anything_else_falls_back_to_its_fields(): void
    {
        $this->assertSame('192.168.0.1: port: 443, expired: nie', ResultText::describe(['ip' => '192.168.0.1', 'port' => 443, 'expired' => false]));
    }
}
