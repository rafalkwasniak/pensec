<?php

namespace App\Models;

use App\Enums\NarrativeStatus;
use App\Enums\NarrativeVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The prose half of a PDF. The facts half is never stored - it is derived from
 * the payload every time the document is rendered, so a report can only ever
 * show numbers that are still in the stored document.
 */
class ReportNarrative extends Model
{
    protected $fillable = [
        'report_id',
        'variant',
        'status',
        'content',
        'model',
        'input_tokens',
        'output_tokens',
        'failure_reason',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'variant' => NarrativeVariant::class,
            'status' => NarrativeStatus::class,
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function isReady(): bool
    {
        return $this->status === NarrativeStatus::Ready && filled($this->content);
    }
}
