<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'public_id',
        'holder_name',
        'score',
        'correct_count',
        'total_count',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'correct_count' => 'integer',
            'total_count' => 'integer',
        ];
    }

    /** Certificates are addressed by their public id, never the auto-increment key. */
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public static function issue(string $name, int $score, int $correct, int $total): self
    {
        return static::create([
            'public_id' => static::newPublicId(),
            'holder_name' => $name,
            'score' => $score,
            'correct_count' => $correct,
            'total_count' => $total,
        ]);
    }

    /** 12 hex characters (48 bits). The unique index is the final guard against a collision. */
    public static function newPublicId(): string
    {
        do {
            $id = strtoupper(bin2hex(random_bytes(6)));
        } while (static::where('public_id', $id)->exists());

        return $id;
    }

    /** Grouped for display on the certificate, e.g. 1A2B-3C4D-5E6F. */
    public function formattedId(): string
    {
        return implode('-', str_split($this->public_id, 4));
    }
}
