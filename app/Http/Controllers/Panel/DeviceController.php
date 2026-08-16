<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\StoreDeviceRequest;
use App\Http\Requests\Panel\UpdateDeviceRequest;
use App\Models\Device;
use App\Services\DeviceProvisioning;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(): View
    {
        $devices = Device::withCount('reports')
            ->orderBy('name')
            ->paginate(25);

        return view('panel.devices.index', ['devices' => $devices]);
    }

    public function create(): View
    {
        return view('panel.devices.create');
    }

    public function store(StoreDeviceRequest $request, DeviceProvisioning $provisioning): RedirectResponse
    {
        $result = $provisioning->register($request->string('name')->toString());

        return redirect()
            ->route('panel.devices.index')
            ->with('status', __('panel.devices.created', ['name' => $result['device']->name]))
            // Shown once, on the next page only: the token cannot be read back.
            ->with('token', $result['token']);
    }

    public function edit(Device $device): View
    {
        return view('panel.devices.edit', ['device' => $device]);
    }

    public function update(UpdateDeviceRequest $request, Device $device): RedirectResponse
    {
        $device->update($request->validated());

        return redirect()
            ->route('panel.devices.index')
            ->with('status', __('panel.devices.updated', ['name' => $device->name]));
    }

    public function destroy(Device $device): RedirectResponse
    {
        if ($device->reports()->exists()) {
            return redirect()
                ->route('panel.devices.edit', $device)
                ->with('error', __('panel.devices.delete_blocked', ['name' => $device->name]));
        }

        $name = $device->name;
        $device->delete();

        return redirect()
            ->route('panel.devices.index')
            ->with('status', __('panel.devices.deleted', ['name' => $name]));
    }
}
