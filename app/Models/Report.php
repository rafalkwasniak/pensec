<?php

namespace App\Models;

use App\Enums\NarrativeStatus;
use App\Enums\NarrativeVariant;
use App\Enums\ReportStatus;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    protected $fillable = [
        'device_id',
        'report_uid',
        'status',
        'received_at',
        'payload_bytes',
        'payload_sha256',
        'source_ip',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'received_at' => 'datetime',
            'payload_bytes' => 'integer',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function payload(): HasOne
    {
        return $this->hasOne(ReportPayload::class);
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
     * The stored document, decoded. Kept here so nothing else has to remember
     * that the payload is deliberately held as a string.
     *
     * @return array<string, mixed>
     */
    public function document(): array
    {
        $decoded = json_decode($this->payload->payload, true);

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
