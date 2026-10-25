<?php

namespace App\Services;

use App\Models\Client;

/**
 * Issues the one-time "set your password" link for a client. Mirrors the device
 * token flow: a random token is generated, only its hash is stored, and the full
 * URL is returned once. Reissuing overwrites the hash, invalidating the previous
 * link. Email-independent by design - the admin hands the link over however they
 * like.
 */
class ClientProvisioning
{
    public function issueSetupLink(Client $client): string
    {
        $token = bin2hex(random_bytes(32));

        $client->update(['password_setup_token_hash' => hash('sha256', $token)]);

        return route('client.password.setup', $token);
    }

    /** The client a setup token belongs to, or null if it is unknown or spent. */
    public function clientForToken(string $token): ?Client
    {
        return Client::query()
            ->whereNotNull('password_setup_token_hash')
            ->where('password_setup_token_hash', hash('sha256', $token))
            ->first();
    }
}
