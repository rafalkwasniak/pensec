<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One contact-form enquiry. Captured in the database and emailed to the address
 * in config('pensec.leads.notify_to'); the panel lists them and marks them
 * handled.
 */
class Lead extends Model
{
    protected $fillable = [
        'name',
        'email',
        'company',
        'message',
        'source_ip',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'referrer',
        'landing_page',
        'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    /**
     * The channel in one line: the tagged campaign when there is one, else the
     * referring site, else a direct visit.
     */
    public function sourceLabel(): string
    {
        if ($this->utm_source !== null) {
            return implode(' / ', array_filter([$this->utm_source, $this->utm_medium, $this->utm_campaign]));
        }

        return $this->referrer ?? 'wejście bezpośrednie';
    }
}
