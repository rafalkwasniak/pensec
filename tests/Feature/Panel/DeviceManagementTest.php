<?php

namespace Tests\Feature\Panel;

use App\Enums\DeviceStatus;
use App\Models\Device;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_it_lists_devices_with_their_report_counts(): void
    {
        $device = Device::factory()->create(['name' => 'Sonda magazyn']);
        Report::factory()->count(2)->create(['device_id' => $device->id]);

        $this->get('/panel/devices')
            ->assertOk()
            ->assertSee('Sonda magazyn')
            ->assertSee($device->token_prefix);
    }

    public function test_adding_a_device_shows_its_token_exactly_once(): void
    {
        $response = $this->post('/panel/devices', ['name' => 'Sonda biuro']);

        $token = session('token');

        $this->assertNotNull($token);
        $this->assertSame(64, strlen($token));

        $device = Device::sole();
        $this->assertSame('Sonda biuro', $device->name);
        $this->assertSame(Device::hashToken($token), $device->token_hash);
        $this->assertTrue($device->isActive());

        // The token is flashed, so it survives exactly one page and no longer.
        $response->assertRedirect('/panel/devices');
        $this->get('/panel/devices')->assertSee($token);
        $this->get('/panel/devices')->assertDontSee($token);
    }

    public function test_the_stored_token_is_only_a_hash(): void
    {
        $this->post('/panel/devices', ['name' => 'Sonda biuro']);

        $token = session('token');

        $this->assertDatabaseMissing('devices', ['token_hash' => $token]);
    }

    public function test_a_device_needs_a_name(): void
    {
        $this->from('/panel/devices/new')
            ->post('/panel/devices', ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertSame(0, Device::count());
    }

    public function test_a_device_can_be_renamed_and_disabled(): void
    {
        $device = Device::factory()->create(['name' => 'Stara nazwa']);

        $this->post("/panel/devices/{$device->id}", [
            'name' => 'Nowa nazwa',
            'status' => 'disabled',
        ])->assertRedirect('/panel/devices');

        $device->refresh();

        $this->assertSame('Nowa nazwa', $device->name);
        $this->assertSame(DeviceStatus::Disabled, $device->status);
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $device = Device::factory()->create();

        $this->from("/panel/devices/{$device->id}")
            ->post("/panel/devices/{$device->id}", ['name' => 'Sonda', 'status' => 'retired'])
            ->assertSessionHasErrors('status');

        $this->assertSame(DeviceStatus::Active, $device->fresh()->status);
    }

    public function test_a_device_without_reports_can_be_deleted(): void
    {
        $device = Device::factory()->create();

        $this->post("/panel/devices/{$device->id}/delete")
            ->assertRedirect('/panel/devices');

        $this->assertSame(0, Device::count());
    }

    public function test_a_device_with_reports_cannot_be_deleted(): void
    {
        $device = Device::factory()->create(['name' => 'Sonda z historią']);
        Report::factory()->create(['device_id' => $device->id]);

        $this->post("/panel/devices/{$device->id}/delete")
            ->assertRedirect("/panel/devices/{$device->id}")
            ->assertSessionHas('error');

        $this->assertSame(1, Device::count());
    }

    public function test_reissuing_a_token_invalidates_the_previous_one(): void
    {
        $device = Device::factory()->create();
        $previousHash = $device->token_hash;

        $this->post("/panel/devices/{$device->id}/token")->assertRedirect('/panel/devices');

        $newToken = session('token');

        $device->refresh();

        $this->assertNotSame($previousHash, $device->token_hash);
        $this->assertSame(Device::hashToken($newToken), $device->token_hash);
        $this->assertSame(substr($newToken, 0, 8), $device->token_prefix);
    }
}
