<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Report extends Model
{
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
