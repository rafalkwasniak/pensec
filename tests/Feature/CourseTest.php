<?php

namespace Tests\Feature;

use Tests\TestCase;

class CourseTest extends TestCase
{
    public function test_the_landing_page_is_public(): void
    {
        $this->get('/course')
            ->assertOk()
            ->assertSee('Kurs Pentesterski');
    }

    public function test_start_redirects_to_the_first_lesson(): void
    {
        $this->get('/course/start')
            ->assertRedirect('/course/00-wprowadzenie/01');
    }

    public function test_a_lesson_renders_with_its_manifest_name(): void
    {
        // The title comes from resources/course/structure.json, not the file
        // name, so this proves the manifest is what names the lesson.
        $this->get('/course/00-wprowadzenie/01')
            ->assertOk()
            ->assertSee('Wprowadzenie do testów penetracyjnych')
            ->assertSee('Początek kursu');
    }

    public function test_a_lesson_absent_from_the_manifest_is_not_found(): void
    {
        // The file pattern matches, but the manifest does not list it.
        $this->get('/course/00-wprowadzenie/zzz')->assertNotFound();
    }

    public function test_an_unknown_chapter_is_not_found(): void
    {
        $this->get('/course/99-nie-ma/01')->assertNotFound();
    }

    /**
     * The manifest is the whole defence: only lessons it lists are served, and
     * the slug is never used to build a path. Pin down that nothing can be
     * reached outside the enumerated set.
     */
    public function test_it_cannot_be_walked_out_of_the_course_directory(): void
    {
        foreach ([
            '/course/../.env',
            '/course/..%2F..%2F.env',
            '/course/....//....//.env',
            '/course/%2e%2e%2f.env',
            '/course/00-wprowadzenie/../../config/app',
        ] as $attempt) {
            $this->get($attempt)->assertNotFound();
        }
    }
}
