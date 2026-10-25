<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\ClientProvisioning;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The one-time "set your password" link. The token authorises the request (no
 * login yet); an unknown or already-spent token is a 404. On success the token
 * is cleared (single use) and the client is logged in.
 */
class PasswordSetupController extends Controller
{
    public function create(string $token, ClientProvisioning $provisioning): View
    {
        abort_if($provisioning->clientForToken($token) === null, 404);

        return view('client.set-password', ['token' => $token]);
    }

    public function store(string $token, Request $request, ClientProvisioning $provisioning): RedirectResponse
    {
        $client = $provisioning->clientForToken($token);

        abort_if($client === null, 404);

        $request->validate(['password' => ['required', 'confirmed', Password::min(12)]]);

        // The 'hashed' cast hashes the plaintext; the token is spent.
        $client->update([
            'password' => $request->string('password')->toString(),
            'password_setup_token_hash' => null,
        ]);

        Auth::guard('client')->login($client);
        $request->session()->regenerate();

        return redirect()->route('client.dashboard')->with('status', 'Hasło zostało ustawione. Witaj w portalu.');
    }
}
