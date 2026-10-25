<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
        ];
    }

    /** A pending account: no password yet, a known setup token for the test. */
    public function withSetupToken(string $token): static
    {
        return $this->state(fn (): array => [
            'password' => null,
            'password_setup_token_hash' => hash('sha256', $token),
        ]);
    }
}
