<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    /**
     * The course structure - chapter order, chapter titles and lesson names -
     * is read from resources/course/structure.json, NOT derived from file and
     * directory names. The .md files hold the content; the manifest names and
     * orders it. A lesson is reachable only when the manifest lists it and its
     * file exists on disk.
     *
     * @return array{0: array<string, array{kicker: string, title: string, files: array<string, string>}>, 1: list<string>}
     */
    private function outline(): array
    {
        $manifestPath = resource_path('course/structure.json');

        if (! File::exists($manifestPath)) {
            return [[], []];
        }

        $manifest = json_decode(File::get($manifestPath), true);

        $outline = [];
        $flatList = [];

        foreach ($manifest['chapters'] ?? [] as $chapter) {
            $dir = $chapter['dir'] ?? null;

            if (! is_string($dir) || ! File::isDirectory(resource_path("course/{$dir}"))) {
                continue;
            }

            $files = [];
            foreach ($chapter['lessons'] ?? [] as $file => $title) {
                $slug = "{$dir}/{$file}";

                if (! File::exists(resource_path("course/{$slug}.md"))) {
                    continue;
                }

                $files[$slug] = $title;
                $flatList[] = $slug;
            }

            if ($files !== []) {
                $outline[$dir] = [
                    'kicker' => $chapter['kicker'] ?? '',
                    'title' => $chapter['title'] ?? $dir,
                    'files' => $files,
                ];
            }
        }

        return [$outline, $flatList];
    }

    public function show(string $slug)
    {
        [$outline, $flatList] = $this->outline();

        // The slug is never used to build the path: a lesson is served only
        // when it appears in the manifest-derived list, so there is no way to
        // reach a file outside the enumerated set.
        if (! in_array($slug, $flatList, true)) {
            abort(404);
        }

        // Lesson content is authored in the repo, but escaping raw HTML and
        // refusing unsafe link schemes is cheap defence-in-depth. Fenced code
        // still keeps its language-* class, so the terminal styling is intact.
        $html = Str::markdown(File::get(resource_path("course/{$slug}.md")), [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
        $readingTime = max(2, (int) ceil(str_word_count(strip_tags($html)) / 200));

        $currentIndex = array_search($slug, $flatList, true);
        $prev = $currentIndex > 0 ? $flatList[$currentIndex - 1] : null;
        $next = $currentIndex < count($flatList) - 1 ? $flatList[$currentIndex + 1] : null;

        $chapterKicker = '';
        $chapterTitle = '';
        $topicTitle = '';
        foreach ($outline as $chapter) {
            if (isset($chapter['files'][$slug])) {
                $chapterKicker = $chapter['kicker'];
                $chapterTitle = $chapter['title'];
                $topicTitle = $chapter['files'][$slug];
                break;
            }
        }

        // "Next chapter" vs "next topic": whether the next lesson sits in the
        // same chapter as the current one.
        $currentDir = explode('/', $slug)[0];
        $nextText = $next !== null && ! isset($outline[$currentDir]['files'][$next])
            ? 'Następny rozdział'
            : 'Następny temat';

        // On the last lesson of a chapter, offer the chapter checkpoint if the
        // chapter ships one. Correct answers stay out of the page; grading is
        // done by checkpoint() on the server.
        $checkpoint = null;
        $checkpointChapter = null;
        $chapterFiles = $outline[$currentDir]['files'] ?? [];
        if ($slug === array_key_last($chapterFiles)) {
            $questions = array_map(
                fn (array $q): array => ['prompt' => $q['prompt'], 'options' => $q['options']],
                $this->checkpoints()[$currentDir]['questions'] ?? [],
            );
            if ($questions !== []) {
                $checkpoint = $questions;
                $checkpointChapter = $currentDir;
            }
        }

        return view('course.reader', compact(
            'html',
            'slug',
            'outline',
            'prev',
            'next',
            'readingTime',
            'chapterKicker',
            'chapterTitle',
            'topicTitle',
            'nextText',
            'checkpoint',
            'checkpointChapter',
        ));
    }

    /**
     * Chapter checkpoints live in resources/course/checkpoints.json, keyed by
     * chapter directory. The 'correct' index stays here and is graded by
     * checkpoint(); it never reaches the browser.
     *
     * @return array<string, array{questions: list<array{prompt: string, options: list<string>, correct: int}>}>
     */
    private function checkpoints(): array
    {
        $path = resource_path('course/checkpoints.json');

        return File::exists($path) ? (json_decode(File::get($path), true) ?: []) : [];
    }

    public function checkpoint(Request $request, string $chapter)
    {
        [$outline] = $this->outline();

        // Only a chapter the manifest lists is gradable, so the slug never
        // reaches content outside the enumerated set.
        abort_unless(isset($outline[$chapter]), 404);

        $questions = $this->checkpoints()[$chapter]['questions'] ?? [];
        abort_if($questions === [], 404);

        $total = count($questions);

        $validated = $request->validate([
            'answers' => ['required', 'array', 'size:'.$total],
            'answers.*' => ['required', 'integer', 'min:0'],
        ]);

        $answers = $validated['answers'];
        $results = [];
        $correct = 0;
        foreach ($questions as $i => $question) {
            $ok = isset($answers[$i]) && (int) $answers[$i] === (int) $question['correct'];
            $results[$i] = $ok;
            if ($ok) {
                $correct++;
            }
        }

        return response()->json([
            'score' => $total > 0 ? (int) round($correct / $total * 100) : 0,
            'correct' => $correct,
            'total' => $total,
            'results' => $results,
        ]);
    }

    public function start()
    {
        [, $flatList] = $this->outline();

        abort_if($flatList === [], 404);

        return redirect('/course/'.$flatList[0]);
    }
}
