<?php

namespace App\Support;

/**
 * Polish number agreement: 1 port, 2 porty, 5 portów, 22 porty, 112 portów.
 * Getting this wrong is the first thing a Polish reader notices in a document
 * that is supposed to be careful.
 */
class Polish
{
    public static function plural(int $count, string $one, string $few, string $many): string
    {
        if ($count === 1) {
            return $one;
        }

        $units = $count % 10;
        $tens = $count % 100;

        return $units >= 2 && $units <= 4 && ($tens < 12 || $tens > 14) ? $few : $many;
    }

    /** The count and its noun together: "3 ustalenia". */
    public static function count(int $count, string $one, string $few, string $many): string
    {
        return $count.' '.self::plural($count, $one, $few, $many);
    }
}
