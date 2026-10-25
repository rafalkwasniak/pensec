<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A portal account. Authenticated on the `client` guard, fully separate from the
 * admin `User`. A client owns devices and, through them, sees only their own
 * reports. The password is set by the client through a one-time link, so it is
 * null until then; only the setup token's hash is stored.
 */
#[Fillable(['name', 'email', 'password', 'password_setup_token_hash'])]
#[Hidden(['password', 'remember_token', 'password_setup_token_hash'])]
class Client extends Authenticatable
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
