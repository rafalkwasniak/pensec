<?php

namespace App\Support;

/**
 * Finds values inside a JSON document without decoding it.
 *
 * A report runs to hundreds of megabytes and decodes to roughly 5.6 times its
 * own size, so a whole json_decode is exactly what took the API down. The few
 * things the API reads out of a report - its id, whether `report` is an object,
 * when the scan ran - sit near the top, and this walks only far enough to find
 * them, returning byte offsets into the string it was given.
 *
 * Callers must pass JSON that json_validate() accepted. That is what keeps this
 * small: on valid input, skipping a value only means counting brackets outside
 * strings, and a string only ends at a quote no backslash escapes.
 */
class JsonOutline
{
    private const WHITESPACE = " \t\n\r";

    /**
     * What kind of value starts at the offset: object, array, string, number,
     * true, false or null.
     */
    public static function type(string $json, int $offset = 0): string
    {
        return match ($json[self::skipWhitespace($json, $offset)] ?? '') {
            '{' => 'object',
            '[' => 'array',
            '"' => 'string',
            't' => 'true',
            'f' => 'false',
            'n' => 'null',
            default => 'number',
        };
    }

    /**
     * Offsets at which the requested members of the object at $offset begin.
     * A key that is absent is absent from the result. Walking stops as soon as
     * every requested key has been seen, so a key near the top of a large
     * object costs nothing for the rest of it. If a key repeats, the first one
     * wins.
     *
     * @param  list<string>  $keys
     * @return array<string, int>
     */
    public static function members(string $json, array $keys, int $offset = 0): array
    {
        $at = self::skipWhitespace($json, $offset);

        if (($json[$at] ?? '') !== '{') {
            return [];
        }

        $wanted = array_flip($keys);
        $found = [];
        $at = self::skipWhitespace($json, $at + 1);

        while (($json[$at] ?? '}') !== '}') {
            $keyEnd = self::endOfString($json, $at);
            $key = json_decode(substr($json, $at, $keyEnd - $at));

            // The colon, then the value itself.
            $value = self::skipWhitespace($json, self::skipWhitespace($json, $keyEnd) + 1);

            if (isset($wanted[$key]) && ! isset($found[$key])) {
                $found[$key] = $value;

                if (count($found) === count($wanted)) {
                    break;
                }
            }

            $at = self::skipWhitespace($json, self::end($json, $value));

            if (($json[$at] ?? '') === ',') {
                $at = self::skipWhitespace($json, $at + 1);
            }
        }

        return $found;
    }

    /**
     * Offsets of each element of the array at $offset, one at a time. Lets a
     * caller decode a list of thousands of entries one entry at a time instead
     * of all of them at once. Yields nothing for anything that is not an array.
     *
     * @return \Generator<int, int>
     */
    public static function elements(string $json, int $offset = 0): \Generator
    {
        $at = self::skipWhitespace($json, $offset);

        if (($json[$at] ?? '') !== '[') {
            return;
        }

        $at = self::skipWhitespace($json, $at + 1);

        while (($json[$at] ?? ']') !== ']') {
            yield $at;

            $at = self::skipWhitespace($json, self::end($json, $at));

            if (($json[$at] ?? '') === ',') {
                $at = self::skipWhitespace($json, $at + 1);
            }
        }
    }

    /**
     * Decodes the value at $offset, whatever it is, as associative arrays.
     */
    public static function decode(string $json, int $offset): mixed
    {
        $start = self::skipWhitespace($json, $offset);

        return json_decode(substr($json, $start, self::end($json, $start) - $start), true);
    }

    /**
     * Decodes the value at $offset, but only a scalar no longer than $maxBytes.
     * Anything else comes back as null, so a hostile document cannot make the
     * caller decode a hundred megabytes by putting them where a short string
     * was expected.
     */
    public static function scalar(string $json, int $offset, int $maxBytes = 1024): string|int|float|bool|null
    {
        $start = self::skipWhitespace($json, $offset);

        if (in_array($json[$start] ?? '', ['{', '['], true)) {
            return null;
        }

        $length = self::end($json, $start) - $start;

        return $length <= $maxBytes ? json_decode(substr($json, $start, $length)) : null;
    }

    /**
     * Offset just past the value that starts at $offset.
     */
    public static function end(string $json, int $offset): int
    {
        $at = self::skipWhitespace($json, $offset);

        return match ($json[$at] ?? '') {
            '"' => self::endOfString($json, $at),
            '{', '[' => self::endOfContainer($json, $at),
            default => $at + strcspn($json, ','.'}]'.self::WHITESPACE, $at),
        };
    }

    private static function endOfString(string $json, int $quote): int
    {
        $at = $quote + 1;

        while (true) {
            $at += strcspn($json, '"\\', $at);

            if ($json[$at] === '"') {
                return $at + 1;
            }

            // A backslash escapes exactly one character, whatever it is.
            $at += 2;
        }
    }

    private static function endOfContainer(string $json, int $open): int
    {
        $depth = 0;
        $at = $open;

        while (true) {
            $at += strcspn($json, '"{}[]', $at);

            switch ($json[$at]) {
                case '"':
                    $at = self::endOfString($json, $at);

                    continue 2;
                case '{':
                case '[':
                    $depth++;
                    break;
                default:
                    if (--$depth === 0) {
                        return $at + 1;
                    }
            }

            $at++;
        }
    }

    private static function skipWhitespace(string $json, int $offset): int
    {
        return $offset + strspn($json, self::WHITESPACE, $offset);
    }
}
