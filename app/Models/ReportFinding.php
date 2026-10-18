<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One finding derived from a report by ReportFacts and stored for the panel.
 *
 * The shape mirrors ReportFacts::finding(): level, title, ip, where, source,
 * cves, confirmed, note. `fingerprint` is derived in the ParseReport job and
 * keeps a re-parse idempotent. Nothing here is interpreted again - it is the
 * same analysis that feeds the PDF, persisted so a person can filter it.
 */
class ReportFinding extends Model
{
    protected $fillable = [
        'report_id',
        'level',
        'title',
        'ip',
        'where',
        'source',
        'cves',
        'confirmed',
        'note',
        'fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'cves' => 'array',
            'confirmed' => 'boolean',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * The stable identity of a finding within one report, so a re-parse of the
     * same document updates rather than duplicates. Order-independent of the
     * positional list ReportFacts returns.
     *
     * @param  array{level: string, source: string, title: string, ip: ?string, where: ?string}  $finding
     */
    public static function fingerprintFor(array $finding): string
    {
        return hash('sha256', implode('|', [
            $finding['level'],
            $finding['source'],
            $finding['title'],
            $finding['ip'] ?? '',
            $finding['where'] ?? '',
        ]));
    }
}
