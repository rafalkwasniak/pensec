<?php

namespace App\Support;

class Duration
{
    /**
     * A span written the way it is read at a glance. The caller supplies a
     * non-negative number of seconds and puts the sign in front itself, so a
     * delay and a probe whose clock runs ahead can be told apart in the view.
     */
    public static function compact(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($days > 0) {
            return "{$days} d {$hours} h";
        }

        if ($hours > 0) {
            return "{$hours} h {$minutes} min";
        }

        return "{$minutes} min";
    }
}
