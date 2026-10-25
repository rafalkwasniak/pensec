<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The form is rate-limited in production; the limiter is not what these
        // tests exercise, so it is set aside to keep them deterministic.
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
    }

    public function test_the_contact_form_stores_a_lead(): void
    {
        $response = $this->post('/contact', [
            'name' => 'Jan Testowy',
            'email' => 'jan@example.com',
            'company' => 'ACME',
            'message' => 'Proszę o wycenę audytu sieci.',
        ]);

        $response->assertSessionHas('lead_status', 'ok');
        $this->assertDatabaseHas('leads', [
            'name' => 'Jan Testowy',
            'email' => 'jan@example.com',
            'company' => 'ACME',
        ]);
    }

    public function test_the_form_rejects_a_missing_name_email_or_message(): void
    {
        $this->post('/contact', ['company' => 'ACME'])
            ->assertSessionHasErrors(['name', 'email', 'message']);

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_a_filled_honeypot_is_silently_dropped(): void
    {
        $this->post('/contact', [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'spam',
            'website' => 'http://spam.example',
        ])->assertSessionHas('lead_status', 'ok');

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_a_guest_cannot_see_the_leads_in_the_panel(): void
    {
        $this->get('/panel/leads')->assertRedirect('/panel/login');
    }

    public function test_an_administrator_sees_the_leads(): void
    {
        $this->actingAs(User::factory()->create());
        Lead::create(['name' => 'Anna Nowak', 'email' => 'anna@example.com', 'message' => 'Pytanie.']);

        $this->get('/panel/leads')
            ->assertOk()
            ->assertSee('Anna Nowak')
            ->assertSee('anna@example.com');
    }

    public function test_an_enquiry_can_be_marked_handled_and_back(): void
    {
        $this->actingAs(User::factory()->create());
        $lead = Lead::create(['name' => 'Anna', 'email' => 'anna@example.com', 'message' => 'Pytanie.']);

        $this->post("/panel/leads/{$lead->id}/handled")->assertRedirect();
        $this->assertNotNull($lead->refresh()->handled_at);

        $this->post("/panel/leads/{$lead->id}/handled")->assertRedirect();
        $this->assertNull($lead->refresh()->handled_at);
    }

    private function send(): void
    {
        $this->post('/contact', [
            'name' => 'Jan Testowy',
            'email' => 'jan@example.com',
            'message' => 'Proszę o wycenę.',
        ])->assertSessionHas('lead_status', 'ok');
    }

    public function test_a_lead_records_the_campaign_that_brought_the_visitor(): void
    {
        $this->get('/?utm_source=linkedin&utm_medium=social&utm_campaign=nis2')->assertOk();
        $this->get('/wiedza')->assertOk();
        $this->send();

        $lead = Lead::sole();
        $this->assertSame('linkedin', $lead->utm_source);
        $this->assertSame('nis2', $lead->utm_campaign);
        // Browsing on keeps the page the visit started on.
        $this->assertSame('/', $lead->landing_page);
        $this->assertSame('linkedin / social / nis2', $lead->sourceLabel());
    }

    public function test_a_later_campaign_replaces_the_earlier_one(): void
    {
        $this->get('/?utm_source=linkedin');
        $this->get('/wiedza?utm_source=newsletter');
        $this->send();

        $this->assertSame('newsletter', Lead::sole()->utm_source);
    }

    public function test_a_google_ads_click_is_recognised_without_utm_tags(): void
    {
        $this->get('/?gclid=abc123');
        $this->send();

        $this->assertSame('google / cpc', Lead::sole()->sourceLabel());
    }

    public function test_an_untagged_visit_records_the_external_referrer_only(): void
    {
        $this->get('/tests', ['referer' => 'https://www.google.com/search?q=audyt'])->assertOk();
        $this->get('/contact', ['referer' => url('/tests')])->assertOk();
        $this->send();

        $lead = Lead::sole();
        $this->assertSame('www.google.com', $lead->referrer);
        $this->assertSame('/tests', $lead->landing_page);
    }

    public function test_a_direct_visit_reads_as_direct(): void
    {
        $this->get('/contact');
        $this->send();

        $this->assertSame('wejście bezpośrednie', Lead::sole()->sourceLabel());
    }

    public function test_the_panel_shows_the_source(): void
    {
        Lead::create(['name' => 'A', 'email' => 'a@example.com', 'message' => 'x', 'utm_source' => 'linkedin']);

        $this->actingAs(User::factory()->create())
            ->get('/panel/leads')
            ->assertOk()
            ->assertSee('linkedin');
    }
}
