<?php

namespace App\Models;

use App\Enums\NarrativeStatus;
use App\Enums\NarrativeVariant;
use App\Enums\PayloadFormat;
use App\Enums\ReportStatus;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    protected $fillable = [
        'device_id',
        'report_uid',
        'status',
        'received_at',
        'scanned_at',
        'payload_bytes',
        'payload_sha256',
        'payload_path',
        'payload_format',
        'source_ip',
    ];

    protected static function booted(): void
    {
        // The row is the only thing that knows the file exists.
        static::deleted(function (Report $report): void {
            if ($report->payload_path !== null) {
                Storage::disk('local')->delete($report->payload_path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'received_at' => 'datetime',
            'scanned_at' => 'datetime',
            'payload_bytes' => 'integer',
            'payload_format' => PayloadFormat::class,
        ];
    }

    /**
     * Seconds between the start of the scan and the moment the document
     * reached us, or null when the probe did not say when it scanned. A
     * negative result means the probe's clock runs ahead of ours; it is
     * reported as it is rather than hidden, because a probe with a wrong
     * clock is exactly the thing worth seeing.
     */
    public function deliverySeconds(): ?int
    {
        return $this->scanned_at?->diffInSeconds($this->received_at, false);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Where a report's document lives on the local disk. Named by the run id,
     * which is unique, so the file can be found without the database too.
     */
    public static function payloadPathFor(string $reportUid): string
    {
        return 'reports/'.$reportUid.'.json.gz';
    }

    /**
     * The stored document as a stream of uncompressed JSON. Callers read it in
     * chunks; a document can be far larger than anything worth holding whole.
     *
     * @return resource
     */
    public function payloadStream()
    {
        $path = Storage::disk('local')->path($this->payload_path);

        // Said plainly, rather than as a TypeError three calls later: a row
        // whose file is gone is a storage problem somebody has to go and fix.
        $stream = is_file($path) ? fopen('compress.zlib://'.$path, 'rb') : false;

        if ($stream === false) {
            throw new RuntimeException("The stored document of report {$this->report_uid} is missing: {$this->payload_path}");
        }

        return $stream;
    }

    /**
     * Absolute path of the stored gzip file, for serving it as it is.
     */
    public function payloadFile(): string
    {
        return Storage::disk('local')->path($this->payload_path);
    }

    public function narratives(): HasMany
    {
        return $this->hasMany(ReportNarrative::class);
    }

    /**
     * The narrative for one variant, or a fresh unsaved one. Callers get an
     * object to read either way, so nothing has to null-check a status.
     */
    public function narrative(NarrativeVariant $variant): ReportNarrative
    {
        return $this->narratives->firstWhere('variant', $variant)
            ?? new ReportNarrative([
                'report_id' => $this->id,
                'variant' => $variant,
                'status' => NarrativeStatus::Pending,
            ]);
    }

    /**
     * The report, decoded whole. Costs about 5.6 times the document in memory,
     * so it is for small documents and tests; anything that may meet a real
     * report reads it through App\Support\ReportSections instead.
     *
     * @return array<string, mixed>
     */
    public function document(): array
    {
        $decoded = json_decode((string) stream_get_contents($stream = $this->payloadStream()), true);
        fclose($stream);

        if ($this->payload_format === PayloadFormat::Submission) {
            $decoded = $decoded['report'] ?? null;
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * The one shape a report takes in API responses. Everything that returns a
     * report delegates here, so the shapes cannot drift apart.
     *
     * @return array<string, mixed>
     */
    public static function card(?self $report): ?array
    {
        if ($report === null) {
            return null;
        }

        return [
            'report_id' => $report->report_uid,
            'status' => $report->status->value,
            'received_at' => $report->received_at->toIso8601ZuluString(),
            'payload_bytes' => $report->payload_bytes,
            'payload_sha256' => $report->payload_sha256,
        ];
    }
}
