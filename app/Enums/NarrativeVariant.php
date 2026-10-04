<?php

namespace App\Enums;

/**
 * The documents generated from one scan. Expert and Client are the same facts
 * in two registers; Technician is a different document - a step-by-step repair
 * guide built from the ranked findings, not a reading of the whole report.
 */
enum NarrativeVariant: string
{
    case Expert = 'expert';
    case Client = 'client';
    case Technician = 'technician';

    /** The label the panel puts on the button. */
    public function label(): string
    {
        return match ($this) {
            self::Expert => 'Pobierz raport ekspercki',
            self::Client => 'Pobierz raport kliencki',
            self::Technician => 'Pobierz przewodnik naprawy',
        };
    }

    /** Names the file the browser receives. */
    public function slug(): string
    {
        return match ($this) {
            self::Expert => 'ekspercki',
            self::Client => 'kliencki',
            self::Technician => 'przewodnik-naprawy',
        };
    }

    public function heading(): string
    {
        return match ($this) {
            self::Expert => 'Raport ekspercki',
            self::Client => 'Raport dla klienta',
            self::Technician => 'Przewodnik naprawy',
        };
    }

    /** The one-line description shown under the heading on the panel card. */
    public function description(): string
    {
        return match ($this) {
            self::Expert => 'Język techniczny, dla administratora sieci.',
            self::Client => 'Prosty język, dla osoby nietechnicznej.',
            self::Technician => 'Kroki naprawy dla technika: co ustawić i gdzie.',
        };
    }

    /** A repair guide, not a reading of the whole report - it renders differently. */
    public function isRemediation(): bool
    {
        return $this === self::Technician;
    }
}
