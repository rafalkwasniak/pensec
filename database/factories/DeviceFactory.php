<?php

namespace Database\Factories;

use App\Enums\DeviceStatus;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $token = Device::generateToken();

        return [
            'name' => fake()->words(2, true).' probe',
            'token_hash' => Device::hashToken($token),
            'token_prefix' => substr($token, 0, config('pensec.devices.token_prefix_length')),
            'status' => DeviceStatus::Active,
            'last_seen_at' => null,
        ];
    }

    /**
     * Registers a device whose plaintext token the test already knows.
     */
    public function withToken(string $token): static
    {
        return $this->state(fn (): array => [
            'token_hash' => Device::hashToken($token),
            'token_prefix' => substr($token, 0, config('pensec.devices.token_prefix_length')),
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => ['status' => DeviceStatus::Disabled]);
    }
}
