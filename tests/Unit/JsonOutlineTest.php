<?php

namespace Tests\Unit;

use App\Support\JsonOutline;
use PHPUnit\Framework\TestCase;

class JsonOutlineTest extends TestCase
{
    /**
     * Decodes whatever starts at an offset, the long way, to check the outline
     * against json_decode itself.
     */
    private function valueAt(string $json, int $offset): mixed
    {
        return json_decode(substr($json, $offset, JsonOutline::end($json, $offset) - $offset), true);
    }

    public function test_it_finds_members_whatever_lies_before_them(): void
    {
        $json = '{"a": {"x": [1, {"y": "}]{["}], "z": "\\"}"}, "b" : [ ] , "c":"tail\\\\"}';

        $members = JsonOutline::members($json, ['a', 'b', 'c']);

        $this->assertSame(['x' => [1, ['y' => '}]{[']], 'z' => '"}'], $this->valueAt($json, $members['a']));
        $this->assertSame([], $this->valueAt($json, $members['b']));
        $this->assertSame('tail\\', $this->valueAt($json, $members['c']));
    }

    public function test_an_absent_key_is_absent_from_the_result(): void
    {
        $this->assertSame([], JsonOutline::members('{"a": 1}', ['b']));
    }

    public function test_it_stops_at_the_last_key_it_was_asked_for(): void
    {
        // Everything after "id" is truncated garbage: reaching it would fail.
        $members = JsonOutline::members('{"id": "r1", "rest": [[[', ['id']);

        $this->assertSame('r1', JsonOutline::scalar('{"id": "r1", "rest": [[[', $members['id']));
    }

    public function test_the_first_of_a_repeated_key_wins(): void
    {
        $json = '{"k": 1, "k": 2}';

        $this->assertSame(1, JsonOutline::scalar($json, JsonOutline::members($json, ['k'])['k']));
    }

    public function test_it_reads_members_of_a_nested_object(): void
    {
        $json = '{"report_id": "r1", "report": {"hosts": ["a", "b"], "scan_time": "2026-08-16 13:38:12"}}';

        $report = JsonOutline::members($json, ['report'])['report'];
        $scanTime = JsonOutline::members($json, ['scan_time'], $report)['scan_time'];

        $this->assertSame('2026-08-16 13:38:12', JsonOutline::scalar($json, $scanTime));
    }

    public function test_members_of_something_that_is_not_an_object_are_none(): void
    {
        $this->assertSame([], JsonOutline::members('["a", "b"]', ['a']));
        $this->assertSame([], JsonOutline::members('"text"', ['a']));
    }

    public function test_it_names_the_type_of_a_value(): void
    {
        foreach ([
            '{}' => 'object', ' []' => 'array', '"s"' => 'string', '-1.5e3' => 'number',
            'true' => 'true', 'false' => 'false', 'null' => 'null',
        ] as $json => $type) {
            $this->assertSame($type, JsonOutline::type($json), $json);
        }
    }

    public function test_a_scalar_is_decoded_only_when_small(): void
    {
        $json = '{"short": "abc", "long": "'.str_repeat('x', 100).'", "nested": {"a": 1}}';
        $members = JsonOutline::members($json, ['short', 'long', 'nested']);

        $this->assertSame('abc', JsonOutline::scalar($json, $members['short'], 16));
        $this->assertNull(JsonOutline::scalar($json, $members['long'], 16));
        $this->assertNull(JsonOutline::scalar($json, $members['nested']));
    }

    public function test_it_agrees_with_json_decode_on_awkward_strings(): void
    {
        $document = [
            'unicode' => 'zażółć 😀 "quoted" \\ back\\slash',
            'empty' => '',
            'slashes' => '/opt/rpi\\',
            'deep' => [[[['{' => ['[' => '"']]]]],
            'numbers' => [0, -1, 1.5e-7, PHP_INT_MAX],
        ];

        foreach ([0, JSON_PRETTY_PRINT, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES] as $flags) {
            $json = json_encode($document, $flags);
            $members = JsonOutline::members($json, array_keys($document));

            foreach ($document as $key => $value) {
                $this->assertSame($value, $this->valueAt($json, $members[$key]), "$key with flags $flags");
            }
        }
    }
}
