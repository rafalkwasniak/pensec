<?php

namespace App\Services;

use App\Enums\DeviceStatus;
use App\Models\Device;

class DeviceProvisioning
{
    /**
     * Registers a device and returns its token in clear. This is the only
     * moment the token exists in readable form - afterwards only its hash is
     * stored, so a lost token can be replaced but never recovered.
     *
     * @return array{device: Device, token: string}
     */
    public function register(string $name): array
    {
        $token = Device::generateToken();

        $device = Device::create([
            'name' => $name,
            'token_hash' => Device::hashToken($token),
            'token_prefix' => $this->prefix($token),
            'status' => DeviceStatus::Active,
        ]);

        return ['device' => $device, 'token' => $token];
    }

    /**
     * Issues a fresh token and invalidates the previous one.
     */
    public function reissueToken(Device $device): string
    {
        $token = Device::generateToken();

        $device->update([
            'token_hash' => Device::hashToken($token),
            'token_prefix' => $this->prefix($token),
        ]);

        return $token;
    }

    private function prefix(string $token): string
    {
        return substr($token, 0, config('pensec.devices.token_prefix_length'));
    }
}
