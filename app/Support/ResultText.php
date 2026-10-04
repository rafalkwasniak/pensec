<?php

namespace App\Support;

/**
 * One line of text for one module result, the same in the PDF and in the brief.
 *
 * A probe result is a small object of technical fields. Printed as key: value
 * pairs it reads as a log line; most results carry a sentence that says what
 * happened (`assessment`, or `reason` on a test that could not run), and that
 * sentence, attributed to its device, is what a reader needs.
 */
class ResultText
{
    /** Fields that already say, in words, what the result means. */
    private const SENTENCES = ['assessment', 'reason', 'error', 'message', 'description'];

    public static function describe(mixed $result): string
    {
        if (is_scalar($result)) {
            return (string) $result;
        }

        if (! is_array($result)) {
            return '';
        }

        // Nuclei's matches, folded per template and host by ReportFacts.
        if (isset($result['template'], $result['matches'], $result['name'], $result['ip'])) {
            return $result['name'].' na '.$result['ip'].': '
                .Polish::count((int) $result['matches'], 'trafienie', 'trafienia', 'trafień')
                .' (szablon '.$result['template'].')';
        }

        $where = $result['ip'] ?? $result['source_ip'] ?? $result['target'] ?? null;
        $prefix = is_scalar($where) && (string) $where !== '' ? $where.': ' : '';

        foreach (self::SENTENCES as $key) {
            if (is_string($result[$key] ?? null) && trim($result[$key]) !== '') {
                return $prefix.trim($result[$key]);
            }
        }

        $parts = [];

        foreach ($result as $key => $value) {
            if ($key === 'ip' || ! is_scalar($value)) {
                continue;
            }

            // A false is often the finding itself ("expired: nie"); it must not
            // vanish the way an empty string does.
            $text = is_bool($value) ? ($value ? 'tak' : 'nie') : (string) $value;

            if ($text !== '') {
                $parts[] = $key.': '.$text;
            }
        }

        return $prefix.implode(', ', $parts);
    }
}
