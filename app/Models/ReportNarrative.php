<?php

namespace App\Models;

use App\Enums\NarrativeStatus;
use App\Enums\NarrativeVariant;
use App\Services\ReportFacts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The prose half of a PDF, together with the facts it was written from.
 *
 * The facts are derived from the stored document by ReportFacts when the prose
 * is generated and kept beside it. The document never changes, so they can only
 * hold numbers that are in it; keeping them means the tables in a PDF always
 * match the prose next to them, even after ReportFacts learns to read a report
 * differently, and rendering never re-reads a document of hundreds of megabytes.
 */
class ReportNarrative extends Model
{
    protected $fillable = [
        'report_id',
        'variant',
        'status',
        'content',
        'facts',
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
            'facts' => 'array',
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

    /**
     * The facts this narrative was written from. A narrative generated before
     * facts were kept gets them derived now and stored, so every later
     * download shows the same thing.
     *
     * @return array<string, mixed>
     */
    public function facts(): array
    {
        if ($this->facts === null) {
            $this->forceFill(['facts' => ReportFacts::forReport($this->report)])->save();
        }

        return $this->facts;
    }
}
