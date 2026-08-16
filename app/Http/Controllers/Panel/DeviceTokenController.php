<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\DeviceProvisioning;
use Illuminate\Http\RedirectResponse;

class DeviceTokenController extends Controller
{
    public function store(Device $device, DeviceProvisioning $provisioning): RedirectResponse
    {
        $token = $provisioning->reissueToken($device);

        return redirect()
            ->route('panel.devices.index')
            ->with('status', __('panel.devices.token_reissued', ['name' => $device->name]))
            ->with('token', $token);
    }
}
