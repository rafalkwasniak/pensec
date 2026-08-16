<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The raw document exactly as it was stored. Deliberately uncast: the payload
 * stays a JSON string so that reading it back never reshapes it.
 */
class ReportPayload extends Model
{
    protected $fillable = [
        'report_id',
        'payload',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
