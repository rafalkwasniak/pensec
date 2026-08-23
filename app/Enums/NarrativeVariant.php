<?php

namespace App\Enums;

/**
 * The two readings of one scan. Same facts, same numbers, different audience -
 * only the wording the model is asked for changes between them.
 */
enum NarrativeVariant: string
{
    case Expert = 'expert';
    case Client = 'client';

    /** The label the panel puts on the button. */
    public function label(): string
    {
        return match ($this) {
            self::Expert => 'Pobierz raport ekspercki',
            self::Client => 'Pobierz raport kliencki',
        };
    }

    /** Names the file the browser receives. */
    public function slug(): string
    {
        return match ($this) {
            self::Expert => 'ekspercki',
            self::Client => 'kliencki',
        };
    }

    public function heading(): string
    {
        return match ($this) {
            self::Expert => 'Raport ekspercki',
            self::Client => 'Raport dla klienta',
        };
    }
}
