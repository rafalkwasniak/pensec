<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\ClientProvisioning;
use Illuminate\Http\RedirectResponse;

/**
 * Re-issues a client's one-time "set password" link, invalidating the previous
 * one. Mirrors DeviceTokenController. Used when a client lost the link or needs
 * to reset their password (admin-driven, email-independent).
 */
class ClientPasswordLinkController extends Controller
{
    public function store(Client $client, ClientProvisioning $provisioning): RedirectResponse
    {
        $url = $provisioning->issueSetupLink($client);

        return redirect()->route('panel.clients.edit', $client)
            ->with('status', 'Wystawiono nowy link do ustawienia hasła. Poprzedni przestał działać.')
            ->with('setup_url', $url);
    }
}
