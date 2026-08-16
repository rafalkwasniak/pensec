<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'token_hash',
        'token_prefix',
        'status',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeviceStatus::class,
            'last_seen_at' => 'datetime',
        ];
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(config('pensec.devices.token_bytes')));
    }

    /**
     * Tokens are looked up by hash, so this must stay a plain, unsalted digest -
     * a per-record salt would make the lookup impossible without a full scan.
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function isActive(): bool
    {
        return $this->status === DeviceStatus::Active;
    }
}
