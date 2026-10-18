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
        $checkpoints = $this->checkpoints();

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

            if ($files === []) {
                continue;
            }

            // A chapter that ships a checkpoint gets a final "Sprawdź się" topic -
            // its own navigable step, not a block hidden under the last lesson.
            if (($checkpoints[$dir]['questions'] ?? []) !== []) {
                $files["{$dir}/sprawdz-sie"] = 'Sprawdź się';
                $flatList[] = "{$dir}/sprawdz-sie";
            }

            $outline[$dir] = [
                'kicker' => $chapter['kicker'] ?? '',
                'title' => $chapter['title'] ?? $dir,
                'files' => $files,
            ];
        }

        // Closing chapter, after all course content: the book.
        if ($flatList !== []) {
            $outline['po-kursie'] = [
                'kicker' => 'Po kursie',
                'title' => 'Materiały dodatkowe',
                'files' => ['po-kursie/ksiazka' => 'Książka o bazach danych'],
            ];
            $flatList[] = 'po-kursie/ksiazka';
        }

        return [$outline, $flatList];
    }

    public function show(string $slug)
    {
        [$outline, $flatList] = $this->outline();

        // The slug is never used to build the path: a lesson or synthetic topic
        // is served only when it appears in the manifest-derived list, so there
        // is no way to reach content outside the enumerated set.
        if (! in_array($slug, $flatList, true)) {
            abort(404);
        }

        $currentDir = explode('/', $slug)[0];

        // Chapter context and topic title come from the outline for every slug,
        // whether a real lesson or a synthetic topic.
        $chapterKicker = $outline[$currentDir]['kicker'] ?? '';
        $chapterTitle = $outline[$currentDir]['title'] ?? '';
        $topicTitle = $outline[$currentDir]['files'][$slug] ?? '';

        $currentIndex = array_search($slug, $flatList, true);
        $prev = $currentIndex > 0 ? $flatList[$currentIndex - 1] : null;
        $next = $currentIndex < count($flatList) - 1 ? $flatList[$currentIndex + 1] : null;

        $nextText = 'Następny temat';
        if ($next !== null) {
            if (str_ends_with($next, '/sprawdz-sie')) {
                $nextText = 'Sprawdź się';
            } elseif (str_starts_with($next, 'po-kursie/')) {
                $nextText = 'Po kursie';
            } elseif (explode('/', $next)[0] !== $currentDir) {
                $nextText = 'Następny rozdział';
            }
        }

        // Three kinds of page: a checkpoint topic, the book topic, or a real
        // lesson. Checkpoint answers stay off the page; grading is server-side.
        $html = '';
        $readingTime = null;
        $checkpoint = null;
        $checkpointChapter = null;
        $bookTopic = false;

        if (str_ends_with($slug, '/sprawdz-sie')) {
            $checkpoint = array_map(
                fn (array $q): array => ['prompt' => $q['prompt'], 'options' => $q['options']],
                $this->checkpoints()[$currentDir]['questions'] ?? [],
            );
            $checkpointChapter = $currentDir;
        } elseif ($slug === 'po-kursie/ksiazka') {
            $bookTopic = true;
        } else {
            // Lesson content is authored in the repo, but escaping raw HTML and
            // refusing unsafe link schemes is cheap defence-in-depth. Fenced
            // code keeps its language-* class, so terminal styling is intact.
            $html = Str::markdown(File::get(resource_path("course/{$slug}.md")), [
                'html_input' => 'escape',
                'allow_unsafe_links' => false,
            ]);
            $readingTime = max(2, (int) ceil(str_word_count(strip_tags($html)) / 200));
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
            'bookTopic',
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
