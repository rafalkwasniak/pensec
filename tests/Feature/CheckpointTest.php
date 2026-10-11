<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CheckpointTest extends TestCase
{
    /** @return list<int> */
    private function correctAnswers(string $chapter): array
    {
        $all = json_decode(File::get(resource_path('course/checkpoints.json')), true);

        return array_map(fn (array $q): int => $q['correct'], $all[$chapter]['questions']);
    }

    public function test_checkpoint_appears_on_the_last_lesson_without_answers(): void
    {
        // 0C is the last lesson of the intro chapter per the manifest order.
        $this->get('/course/00-wprowadzenie/0C')
            ->assertOk()
            ->assertSee('Sprawdź się')
            ->assertSee('Assume Breach')
            ->assertDontSee('"correct"', false);
    }

    public function test_checkpoint_is_absent_on_a_non_last_lesson(): void
    {
        $this->get('/course/00-wprowadzenie/01')
            ->assertOk()
            ->assertDontSee('Sprawdź się');
    }

    public function test_checkpoint_grades_on_the_server(): void
    {
        $this->postJson('/course/checkpoint/00-wprowadzenie', [
            'answers' => $this->correctAnswers('00-wprowadzenie'),
        ])
            ->assertOk()
            ->assertJson(['score' => 100, 'results' => [true, true, true]]);
    }

    public function test_checkpoint_of_the_wrong_length_is_rejected(): void
    {
        $this->postJson('/course/checkpoint/00-wprowadzenie', ['answers' => [0]])
            ->assertStatus(422);
    }

    public function test_checkpoint_for_an_unknown_chapter_is_not_found(): void
    {
        $this->postJson('/course/checkpoint/99-nie-ma', ['answers' => [0, 0, 0]])
            ->assertNotFound();
    }
}
