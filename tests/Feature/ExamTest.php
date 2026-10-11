<?php

namespace Tests\Feature;

use App\Models\Certificate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ExamTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, array{prompt: string, options: list<string>, correct: int}> */
    private function steps(): array
    {
        return json_decode(File::get(resource_path('course/exam.json')), true)['steps'];
    }

    /** @return list<int> */
    private function correctAnswers(): array
    {
        return array_map(fn (array $s): int => $s['correct'], $this->steps());
    }

    public function test_the_exam_page_never_ships_the_answers(): void
    {
        // The browser gets each step and its options, never the 'correct' index.
        $this->get('/course/exam')
            ->assertOk()
            ->assertSee('Egzamin końcowy')
            ->assertDontSee('"correct"', false);
    }

    public function test_a_perfect_score_passes_but_issues_no_certificate_without_a_name(): void
    {
        $this->postJson('/course/exam', ['answers' => $this->correctAnswers()])
            ->assertOk()
            ->assertJson(['passed' => true, 'score' => 100, 'certificate_url' => null]);

        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_passing_with_a_name_issues_a_verifiable_certificate(): void
    {
        $url = $this->postJson('/course/exam', [
            'answers' => $this->correctAnswers(),
            'name' => 'Jan Kowalski',
        ])->assertOk()->assertJson(['passed' => true])->json('certificate_url');

        $this->assertNotNull($url);

        $certificate = Certificate::first();
        $this->assertSame('Jan Kowalski', $certificate->holder_name);
        $this->assertSame(100, $certificate->score);

        $this->get($url)->assertOk()->assertSee('Jan Kowalski');
    }

    public function test_a_failing_score_never_issues_a_certificate(): void
    {
        $wrong = array_map(
            fn (array $s): int => ($s['correct'] + 1) % count($s['options']),
            $this->steps(),
        );

        $this->postJson('/course/exam', ['answers' => $wrong, 'name' => 'Jan Kowalski'])
            ->assertOk()
            ->assertJson(['passed' => false]);

        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_a_submission_of_the_wrong_length_is_rejected(): void
    {
        $this->postJson('/course/exam', ['answers' => [0, 1, 2]])
            ->assertStatus(422);
    }

    public function test_a_made_up_certificate_id_is_not_found(): void
    {
        $this->get('/course/certificate/DEADBEEFCAFE')->assertNotFound();
    }

    public function test_the_certificate_downloads_as_pdf(): void
    {
        $certificate = Certificate::issue('Jan Kowalski', 100, 12, 12);

        $this->get(route('course.certificate.pdf', $certificate))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
