<?php

namespace App\Enums;

/**
 * Where one narrative is in its life. The panel polls this while a job runs, so
 * every value here is something a person may see on screen.
 */
enum NarrativeStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'W kolejce',
            self::Processing => 'Generowanie trwa',
            self::Ready => 'Gotowy',
            self::Failed => 'Nie udało się',
        };
    }

    /** True while a job is expected to move this on by itself. */
    public function inProgress(): bool
    {
        return $this === self::Pending || $this === self::Processing;
    }
}
