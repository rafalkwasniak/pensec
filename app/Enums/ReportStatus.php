<?php

namespace App\Enums;

/**
 * Where a stored report is in its processing life.
 *
 * `received` is the state at intake: the raw document is stored and queued for
 * analysis. A queued ParseReport job moves it to `parsing`, then to `parsed`
 * once the findings are derived and stored, or `failed` if it could not finish.
 * A `failed` parse never touches the stored document - the raw report is still
 * the source of truth. These values are published in the API contract
 * (ReportStatus), so adding one here means growing the enum there too.
 */
enum ReportStatus: string
{
    case Received = 'received';
    case Parsing = 'parsing';
    case Parsed = 'parsed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'W kolejce do analizy',
            self::Parsing => 'Analiza trwa',
            self::Parsed => 'Przeanalizowany',
            self::Failed => 'Analiza nie udała się',
        };
    }

    /** True while a parse job is expected to move this on by itself. */
    public function inProgress(): bool
    {
        return $this === self::Received || $this === self::Parsing;
    }
}
