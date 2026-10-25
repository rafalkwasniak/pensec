<?php

namespace Tests\Feature\Panel;

use App\Models\Client;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): void
    {
        $this->actingAs(User::factory()->create());
    }

    public function test_a_guest_cannot_manage_clients(): void
    {
        $this->get('/panel/clients')->assertRedirect('/panel/login');
    }

    public function test_admin_creates_a_client_and_gets_a_one_time_setup_link(): void
    {
        $this->signIn();

        $response = $this->post('/panel/clients', ['name' => 'ACME Sp. z o.o.', 'email' => 'kontakt@acme.test']);

        $response->assertRedirect()->assertSessionHas('setup_url');

        $client = Client::sole();
        $this->assertSame('ACME Sp. z o.o.', $client->name);
        $this->assertNull($client->password);
        $this->assertNotNull($client->password_setup_token_hash);
    }

    public function test_creating_requires_a_name_and_a_unique_email(): void
    {
        $this->signIn();
        Client::factory()->create(['email' => 'taken@acme.test']);

        $this->from('/panel/clients/new')->post('/panel/clients', ['email' => 'taken@acme.test'])
            ->assertSessionHasErrors(['name', 'email']);

        $this->assertSame(1, Client::count());
    }

    public function test_admin_assigns_and_unassigns_devices(): void
    {
        $this->signIn();
        $client = Client::factory()->create();
        $a = Device::factory()->create();
        $b = Device::factory()->create();

        $this->post(route('panel.clients.update', $client), [
            'name' => $client->name,
            'email' => $client->email,
            'devices' => [$a->id],
        ])->assertRedirect();

        $this->assertSame($client->id, $a->refresh()->client_id);
        $this->assertNull($b->refresh()->client_id);

        // Unchecking removes the assignment.
        $this->post(route('panel.clients.update', $client), [
            'name' => $client->name,
            'email' => $client->email,
            'devices' => [],
        ])->assertRedirect();

        $this->assertNull($a->refresh()->client_id);
    }

    public function test_admin_reissues_the_setup_link_and_invalidates_the_old_one(): void
    {
        $this->signIn();
        $client = Client::factory()->create(['password_setup_token_hash' => hash('sha256', 'old')]);

        $this->post(route('panel.clients.password', $client))->assertRedirect()->assertSessionHas('setup_url');

        $this->assertNotSame(hash('sha256', 'old'), $client->refresh()->password_setup_token_hash);
    }

    public function test_deleting_a_client_unassigns_its_devices_and_keeps_reports(): void
    {
        $this->signIn();
        $client = Client::factory()->create();
        $device = Device::factory()->create(['client_id' => $client->id]);

        $this->post(route('panel.clients.destroy', $client))->assertRedirect('/panel/clients');

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
        $this->assertNull($device->refresh()->client_id);
    }
}
