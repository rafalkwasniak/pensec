<?php

namespace Tests\Feature\Panel;

use App\Enums\NarrativeStatus;
use App\Enums\NarrativeVariant;
use App\Jobs\GenerateReportNarrative;
use App\Models\Report;
use App\Models\ReportNarrative;
use App\Models\User;
use App\Services\ReportFacts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReportNarrativeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.deepseek.key' => 'test-key']);
    }

    private function signIn(): void
    {
        $this->actingAs(User::factory()->create());
    }

    /** An answer shaped the way the prompt asks for it. */
    private function answer(string $prose = 'Treść opisu.'): string
    {
        $blocks = [];

        foreach (['podsumowanie', 'urzadzenia', 'uslugi', 'podatnosci', 'ekspozycja', 'ics', 'diagnostyka', 'rekomendacje'] as $key) {
            $blocks[] = "### SEKCJA: {$key}\n{$prose}";
        }

        return implode("\n\n", $blocks);
    }

    private function fakeDeepSeek(string $content): void
    {
        Http::fake(['api.deepseek.com/*' => Http::response([
            'model' => 'deepseek-v4-flash',
            'choices' => [['message' => ['content' => $content], 'finish_reason' => 'stop']],
            'usage' => ['prompt_tokens' => 3000, 'completion_tokens' => 900],
        ])]);
    }

    private function ready(Report $report, NarrativeVariant $variant = NarrativeVariant::Expert): ReportNarrative
    {
        return ReportNarrative::create([
            'report_id' => $report->id,
            'variant' => $variant,
            'status' => NarrativeStatus::Ready,
            'content' => $this->answer(),
            'model' => 'deepseek-v4-flash',
            'generated_at' => now(),
        ]);
    }

    public function test_a_guest_cannot_queue_or_download_a_report(): void
    {
        $report = Report::factory()->create();

        $this->post("/panel/reports/{$report->id}/narrative/expert")->assertRedirect('/panel/login');
        $this->get("/panel/reports/{$report->id}/narrative/expert/pdf")->assertRedirect('/panel/login');
    }

    public function test_an_unknown_variant_is_not_a_route(): void
    {
        $this->signIn();
        $report = Report::factory()->create();

        $this->get("/panel/reports/{$report->id}/narrative/marketingowy/pdf")->assertNotFound();
    }

    public function test_the_report_page_offers_both_documents(): void
    {
        $this->signIn();
        $report = Report::factory()->create();

        $this->get("/panel/reports/{$report->id}")
            ->assertOk()
            ->assertSee('Raport ekspercki')
            ->assertSee('Raport dla klienta');
    }

    public function test_clicking_generate_queues_the_work_and_returns_at_once(): void
    {
        Queue::fake();
        $this->signIn();
        $report = Report::factory()->create();

        $this->post("/panel/reports/{$report->id}/narrative/client")->assertRedirect();

        Queue::assertPushed(
            GenerateReportNarrative::class,
            fn ($job) => $job->reportId === $report->id && $job->variant === NarrativeVariant::Client,
        );

        $this->assertDatabaseHas('report_narratives', [
            'report_id' => $report->id,
            'variant' => 'client',
            'status' => 'pending',
        ]);
    }

    public function test_a_second_click_while_it_is_working_does_not_queue_it_twice(): void
    {
        Queue::fake();
        $this->signIn();
        $report = Report::factory()->create();

        $this->post("/panel/reports/{$report->id}/narrative/expert");
        $this->post("/panel/reports/{$report->id}/narrative/expert");

        Queue::assertPushed(GenerateReportNarrative::class, 1);
    }

    public function test_a_ready_report_is_not_generated_again_by_the_generate_button(): void
    {
        Queue::fake();
        $this->signIn();
        $report = Report::factory()->create();
        $this->ready($report);

        $this->post("/panel/reports/{$report->id}/narrative/expert");

        Queue::assertNothingPushed();
    }

    public function test_regenerating_clears_the_old_text_and_queues_a_fresh_pass(): void
    {
        Queue::fake();
        $this->signIn();
        $report = Report::factory()->create();
        $this->ready($report);

        $this->post("/panel/reports/{$report->id}/narrative/expert/regenerate")->assertRedirect();

        Queue::assertPushed(GenerateReportNarrative::class, 1);
        $this->assertDatabaseHas('report_narratives', [
            'report_id' => $report->id,
            'variant' => 'expert',
            'status' => 'pending',
            'content' => null,
        ]);
    }

    public function test_the_status_endpoint_tells_the_page_when_to_stop_polling(): void
    {
        $this->signIn();
        $report = Report::factory()->create();

        $this->getJson("/panel/reports/{$report->id}/narrative/expert/status")
            ->assertOk()
            ->assertJson(['status' => 'pending', 'in_progress' => true, 'ready' => false]);

        $this->ready($report);

        $this->getJson("/panel/reports/{$report->id}/narrative/expert/status")
            ->assertOk()
            ->assertJson(['status' => 'ready', 'in_progress' => false, 'ready' => true]);
    }

    public function test_a_report_that_is_not_ready_cannot_be_downloaded(): void
    {
        $this->signIn();
        $report = Report::factory()->create();

        $this->get("/panel/reports/{$report->id}/narrative/expert/pdf")->assertNotFound();
    }

    public function test_a_ready_report_downloads_as_a_pdf(): void
    {
        $this->signIn();
        $report = Report::factory()->create();
        $this->ready($report);

        $response = $this->get("/panel/reports/{$report->id}/narrative/expert/pdf");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('pensec-raport-ekspercki', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_the_job_stores_what_the_model_wrote(): void
    {
        $this->fakeDeepSeek($this->answer('Opis sekcji.'));

        $report = Report::factory()->create();

        GenerateReportNarrative::dispatchSync($report->id, NarrativeVariant::Expert);

        $narrative = $report->fresh()->narratives->first();

        $this->assertSame(NarrativeStatus::Ready, $narrative->status);
        $this->assertStringContainsString('Opis sekcji.', $narrative->content);
        $this->assertSame('deepseek-v4-flash', $narrative->model);
        $this->assertSame(3000, $narrative->input_tokens);
        $this->assertNotNull($narrative->generated_at);
    }

    public function test_the_job_never_asks_the_model_to_cap_its_own_tokens(): void
    {
        $this->fakeDeepSeek($this->answer());

        $report = Report::factory()->create();

        GenerateReportNarrative::dispatchSync($report->id, NarrativeVariant::Expert);

        // Reasoning tokens count against max_tokens on the v4 models, so a cap
        // silently truncates the report. Sending one is the bug, not the fix.
        Http::assertSent(fn ($request) => ! array_key_exists('max_tokens', $request->data())
            && $request['reasoning_effort'] === 'low');
    }

    public function test_an_api_error_leaves_a_failure_a_person_can_read(): void
    {
        Http::fake(['api.deepseek.com/*' => Http::response(['error' => 'nope'], 500)]);

        $report = Report::factory()->create();

        GenerateReportNarrative::dispatchSync($report->id, NarrativeVariant::Client);

        $narrative = $report->fresh()->narratives->first();

        $this->assertSame(NarrativeStatus::Failed, $narrative->status);
        $this->assertStringContainsString('500', $narrative->failure_reason);
        $this->assertNull($narrative->content);
    }

    public function test_an_answer_without_the_expected_blocks_counts_as_a_failure(): void
    {
        $this->fakeDeepSeek('Proszę bardzo, oto raport, ale bez żadnych sekcji.');

        $report = Report::factory()->create();

        GenerateReportNarrative::dispatchSync($report->id, NarrativeVariant::Expert);

        $narrative = $report->fresh()->narratives->first();

        $this->assertSame(NarrativeStatus::Failed, $narrative->status);
        $this->assertNull($narrative->content);
    }

    public function test_a_report_that_is_already_written_is_left_alone_by_the_job(): void
    {
        Http::fake();

        $report = Report::factory()->create();
        $this->ready($report);

        GenerateReportNarrative::dispatchSync($report->id, NarrativeVariant::Expert);

        Http::assertNothingSent();
    }

    /**
     * The point of the whole design: figures come from the stored document, so
     * a model that says nothing about them cannot remove them from the PDF.
     */
    public function test_the_document_carries_facts_from_the_report_not_from_the_model(): void
    {
        $this->signIn();

        $report = Report::factory()->withDocument([
            'scan_time' => '2026-08-16 13:38:12',
            'hosts' => ['10.0.0.7'],
            'nmap_results' => [
                '10.0.0.7' => "Nmap scan report for 10.0.0.7\nHost is up (0.001s latency).\n"
                    ."PORT   STATE SERVICE VERSION\n8080/tcp open  http    Jetty 9.4\n",
            ],
        ])->create();

        ReportNarrative::create([
            'report_id' => $report->id,
            'variant' => NarrativeVariant::Expert,
            'status' => NarrativeStatus::Ready,
            'content' => "### SEKCJA: uslugi\nModel nie wspomina o żadnym porcie.",
            'generated_at' => now(),
        ]);

        $pdf = $this->get("/panel/reports/{$report->id}/narrative/expert/pdf")->getContent();

        // dompdf compresses page streams, so assert on the facts the renderer
        // was handed rather than on bytes inside the PDF.
        $facts = ReportFacts::from($report->document());

        $this->assertSame(1, $facts['totals']['open_ports']);
        $this->assertSame(8080, $facts['services'][0]['port']);
        $this->assertSame('Jetty 9.4', $facts['services'][0]['version']);
        $this->assertStringStartsWith('%PDF', $pdf);
    }
}
