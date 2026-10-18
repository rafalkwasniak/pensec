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

    public function test_checkpoint_renders_as_its_own_topic_without_answers(): void
    {
        // The checkpoint is a navigable "Sprawdź się" topic, not a block hidden
        // under the last lesson.
        $this->get('/course/00-wprowadzenie/sprawdz-sie')
            ->assertOk()
            ->assertSee('id="checkpoint"', false)
            ->assertSee('Assume Breach')
            ->assertDontSee('"correct"', false);
    }

    public function test_checkpoint_is_absent_on_a_content_lesson(): void
    {
        // A content lesson never carries the checkpoint block; the sidebar still
        // links the "Sprawdź się" topic, so assert on the block, not the label.
        $this->get('/course/00-wprowadzenie/01')
            ->assertOk()
            ->assertDontSee('id="checkpoint"', false);
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
