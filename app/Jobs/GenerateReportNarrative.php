<?php

namespace App\Jobs;

use App\Enums\NarrativeStatus;
use App\Enums\NarrativeVariant;
use App\Models\Report;
use App\Models\ReportNarrative;
use App\Services\DeepSeek;
use App\Services\NarrativePrompt;
use App\Services\ReportFacts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes one variant of one report.
 *
 * Runs on the queue because a call to DeepSeek takes far longer than a browser
 * will wait. The row it fills already exists as `pending` before this is
 * dispatched, so the panel has something to poll from the first moment.
 *
 * Idempotent: a narrative that is already ready is left alone. Re-generating is
 * a deliberate act, and it clears the row first.
 */
class GenerateReportNarrative implements ShouldQueue
{
    use Queueable;

    /** One attempt: a retry would bill a second full report for the same click. */
    public int $tries = 1;

    /** Comfortably above the HTTP timeout, so the client fails before the job does. */
    public int $timeout = 300;

    public function __construct(
        public readonly int $reportId,
        public readonly NarrativeVariant $variant,
    ) {}

    public function handle(DeepSeek $deepSeek): void
    {
        $report = Report::with('device', 'payload')->find($this->reportId);

        if ($report === null) {
            Log::warning('Narrative job for a report that is gone', ['report_id' => $this->reportId]);

            return;
        }

        $narrative = ReportNarrative::firstOrNew([
            'report_id' => $report->id,
            'variant' => $this->variant,
        ]);

        if ($narrative->isReady()) {
            return;
        }

        $narrative->fill(['status' => NarrativeStatus::Processing, 'failure_reason' => null])->save();

        $facts = ReportFacts::from($report->document());

        $result = $deepSeek->write(
            NarrativePrompt::system($this->variant),
            NarrativePrompt::user($facts, $report),
        );

        if (! $result['ok']) {
            $this->fail($narrative, $result['error'] ?? 'Nieznany błąd.');

            return;
        }

        // A well-formed answer carries the labelled blocks the template renders.
        // Anything else would leave a document with facts but no commentary, so
        // it counts as a failure rather than a half-finished success.
        if (NarrativePrompt::split($result['content']) === []) {
            $this->fail($narrative, 'Model nie zwrócił żadnej rozpoznawalnej sekcji.');

            return;
        }

        $narrative->fill([
            'status' => NarrativeStatus::Ready,
            'content' => $result['content'],
            'model' => $result['model'] ?? null,
            'input_tokens' => $result['input_tokens'] ?? null,
            'output_tokens' => $result['output_tokens'] ?? null,
            'failure_reason' => null,
            'generated_at' => now(),
        ])->save();
    }

    /** Unhandled throw still has to leave a status a person can read. */
    public function failed(?Throwable $exception): void
    {
        ReportNarrative::where('report_id', $this->reportId)
            ->where('variant', $this->variant)
            ->update([
                'status' => NarrativeStatus::Failed,
                'failure_reason' => 'Zadanie przerwane: '.($exception?->getMessage() ?? 'nieznany powód'),
            ]);
    }

    private function fail(ReportNarrative $narrative, string $reason): void
    {
        Log::warning('Narrative generation failed', [
            'report_id' => $this->reportId,
            'variant' => $this->variant->value,
            'reason' => $reason,
        ]);

        $narrative->fill([
            'status' => NarrativeStatus::Failed,
            'failure_reason' => $reason,
        ])->save();
    }
}
