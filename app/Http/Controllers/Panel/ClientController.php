<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\StoreClientRequest;
use App\Http\Requests\Panel\UpdateClientRequest;
use App\Models\Client;
use App\Models\Device;
use App\Services\ClientProvisioning;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        return view('panel.clients.index', [
            'clients' => Client::withCount('devices')->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('panel.clients.create');
    }

    public function store(StoreClientRequest $request, ClientProvisioning $provisioning): RedirectResponse
    {
        $client = Client::create($request->validated());
        $url = $provisioning->issueSetupLink($client);

        return redirect()->route('panel.clients.edit', $client)
            ->with('status', 'Klient został utworzony. Przekaż mu poniższy link do ustawienia hasła.')
            ->with('setup_url', $url);
    }

    public function edit(Client $client): View
    {
        return view('panel.clients.edit', [
            'client' => $client,
            'devices' => Device::orderBy('name')->get(),
            'assigned' => $client->devices()->pluck('id')->all(),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->safe()->only(['name', 'email']));

        // Device assignment: checked devices join this client; any device that
        // was this client's but is now unchecked is unassigned. Devices of other
        // clients are untouched.
        $chosen = collect($request->input('devices', []))->map(fn ($id): int => (int) $id)->all();

        Device::whereIn('id', $chosen)->update(['client_id' => $client->id]);
        Device::where('client_id', $client->id)->whereNotIn('id', $chosen)->update(['client_id' => null]);

        return redirect()->route('panel.clients.edit', $client)->with('status', 'Zapisano zmiany.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        // Devices unassign (nullOnDelete); the reports they produced are kept.
        $client->delete();

        return redirect()->route('panel.clients.index')->with('status', 'Klient został usunięty.');
    }
}
