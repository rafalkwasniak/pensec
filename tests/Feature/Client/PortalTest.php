<?php

namespace Tests\Feature\Client;

use App\Enums\NarrativeStatus;
use App\Enums\NarrativeVariant;
use App\Models\Client;
use App\Models\Device;
use App\Models\Report;
use App\Models\ReportNarrative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    private function reportFor(Client $client): Report
    {
        $device = Device::factory()->create(['client_id' => $client->id]);

        return Report::factory()->create(['device_id' => $device->id]);
    }

    public function test_a_guest_is_sent_to_the_client_login(): void
    {
        $this->get('/portal')->assertRedirect('/portal/login');
    }

    public function test_the_setup_link_sets_the_password_logs_in_and_is_single_use(): void
    {
        $token = str_repeat('a', 64);
        $client = Client::factory()->withSetupToken($token)->create();

        $this->get("/portal/ustaw-haslo/{$token}")->assertOk();

        $this->post("/portal/ustaw-haslo/{$token}", [
            'password' => 'bardzo-dlugie-haslo-1',
            'password_confirmation' => 'bardzo-dlugie-haslo-1',
        ])->assertRedirect(route('client.dashboard'));

        $this->assertAuthenticated('client');
        $client->refresh();
        $this->assertNotNull($client->password);
        $this->assertNull($client->password_setup_token_hash);

        // The token is spent: the link no longer works.
        $this->get("/portal/ustaw-haslo/{$token}")->assertNotFound();
    }

    public function test_an_unknown_setup_token_is_not_found(): void
    {
        $this->get('/portal/ustaw-haslo/'.str_repeat('b', 64))->assertNotFound();
    }

    public function test_a_client_can_log_in(): void
    {
        $client = Client::factory()->create(); // factory password is 'password'

        $this->post('/portal/login', ['email' => $client->email, 'password' => 'password'])
            ->assertRedirect(route('client.dashboard'));

        $this->assertAuthenticated('client');
    }

    public function test_a_client_only_reaches_their_own_reports(): void
    {
        $mine = Client::factory()->create();
        $other = Client::factory()->create();
        $myReport = $this->reportFor($mine);
        $otherReport = $this->reportFor($other);

        $this->actingAs($mine, 'client');

        $this->get(route('client.reports.show', $myReport))->assertOk();
        $this->get(route('client.reports.show', $otherReport))->assertNotFound();
    }

    public function test_pdf_download_is_scoped_to_the_clients_own_reports(): void
    {
        $mine = Client::factory()->create();
        $other = Client::factory()->create();
        $myReport = $this->reportFor($mine);
        $otherReport = $this->reportFor($other);

        foreach ([$myReport, $otherReport] as $report) {
            ReportNarrative::create([
                'report_id' => $report->id,
                'variant' => NarrativeVariant::Client,
                'status' => NarrativeStatus::Ready,
                'content' => "### SEKCJA: podsumowanie\nTreść.",
                'generated_at' => now(),
            ]);
        }

        $this->actingAs($mine, 'client');

        $response = $this->get(route('client.reports.pdf', [$myReport, NarrativeVariant::Client->value]));
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));

        // Another client's report, and a variant that is not ready, are both 404.
        $this->get(route('client.reports.pdf', [$otherReport, NarrativeVariant::Client->value]))->assertNotFound();
        $this->get(route('client.reports.pdf', [$myReport, NarrativeVariant::Expert->value]))->assertNotFound();
    }

    public function test_a_client_cannot_reach_the_admin_panel(): void
    {
        $this->actingAs(Client::factory()->create(), 'client');

        $this->get('/panel/devices')->assertRedirect('/panel/login');
    }
}
