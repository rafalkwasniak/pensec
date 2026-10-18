<?php

namespace App\Console\Commands;

use App\Enums\ReportStatus;
use App\Jobs\ParseReport;
use App\Models\Report;
use Illuminate\Console\Command;

/**
 * Queues a parse for reports that do not have one yet. New reports are parsed
 * at intake; this is for the ones stored before parsing existed, or to re-run
 * everything after ReportFacts has learned to read a report better.
 */
class ParseReports extends Command
{
    protected $signature = 'pensec:parse-reports {--all : Re-parse every report, not only the unparsed ones}';

    protected $description = 'Queue analysis of stored reports';

    public function handle(): int
    {
        $query = Report::query()
            ->when(! $this->option('all'), fn ($q) => $q->where('status', '!=', ReportStatus::Parsed->value));

        $count = 0;

        $query->orderBy('id')->chunkById(100, function ($reports) use (&$count): void {
            foreach ($reports as $report) {
                if ($this->option('all')) {
                    $report->findings()->delete();
                    $report->forceFill([
                        'status' => ReportStatus::Received,
                        'summary' => null,
                        'parsed_at' => null,
                        'parse_failure_reason' => null,
                    ])->save();
                }

                ParseReport::dispatch($report->id);
                $count++;
            }
        });

        $this->components->info("Queued {$count} report(s) for analysis.");

        return self::SUCCESS;
    }
}
