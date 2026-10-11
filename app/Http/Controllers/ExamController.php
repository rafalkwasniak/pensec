<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ExamController extends Controller
{
    /**
     * The exam is a decision lab: an ordered scenario of steps in
     * resources/course/exam.json. Each step carries optional context (an
     * output or log to read), a type, a prompt, options and the correct index.
     * The 'correct' index stays on the server and is never sent to the browser;
     * grading happens here from this file.
     *
     * @return array{title: string, intro: string, pass_threshold: int, steps: list<array{context?: string, type?: string, prompt: string, options: list<string>, correct: int}>}
     */
    private function exam(): array
    {
        $path = resource_path('course/exam.json');
        abort_unless(File::exists($path), 404);

        $exam = json_decode(File::get($path), true) ?: [];

        return [
            'title' => $exam['title'] ?? 'Egzamin końcowy',
            'intro' => $exam['intro'] ?? '',
            'pass_threshold' => (int) ($exam['pass_threshold'] ?? 75),
            'steps' => $exam['steps'] ?? [],
        ];
    }

    public function show()
    {
        $exam = $this->exam();

        // Hand the browser each step EXCEPT its correct index.
        $steps = array_map(fn (array $s): array => [
            'context' => $s['context'] ?? null,
            'type' => $s['type'] ?? null,
            'prompt' => $s['prompt'],
            'options' => $s['options'],
        ], $exam['steps']);

        return view('course.exam', [
            'title' => $exam['title'],
            'intro' => $exam['intro'],
            'passThreshold' => $exam['pass_threshold'],
            'steps' => $steps,
        ]);
    }

    public function submit(Request $request)
    {
        $exam = $this->exam();
        $steps = $exam['steps'];
        $total = count($steps);

        $validated = $request->validate([
            'answers' => ['required', 'array', 'size:'.$total],
            'answers.*' => ['required', 'integer', 'min:0'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $answers = $validated['answers'];

        $correct = 0;
        foreach ($steps as $i => $step) {
            if (isset($answers[$i]) && (int) $answers[$i] === (int) $step['correct']) {
                $correct++;
            }
        }

        $score = $total > 0 ? (int) round($correct / $total * 100) : 0;
        $passed = $score >= $exam['pass_threshold'];

        // A certificate is issued only on a real pass and only once a name is
        // supplied. The client re-sends its answers with the name, so the
        // server always grades authoritatively before issuing - the browser is
        // never trusted with "I passed".
        $certificateUrl = null;
        $name = trim((string) ($validated['name'] ?? ''));
        if ($passed && $name !== '') {
            $certificate = Certificate::issue($name, $score, $correct, $total);
            $certificateUrl = route('course.certificate', $certificate);
        }

        return response()->json([
            'passed' => $passed,
            'score' => $score,
            'correct' => $correct,
            'total' => $total,
            'certificate_url' => $certificateUrl,
        ]);
    }

    public function certificate(Certificate $certificate)
    {
        return view('course.certificate', ['certificate' => $certificate]);
    }

    public function certificatePdf(Certificate $certificate)
    {
        $pdf = Pdf::loadView('pdf.certificate', ['certificate' => $certificate])
            ->setPaper('a4', 'landscape')
            ->setOption('isFontSubsettingEnabled', true);

        return $pdf->download('certyfikat-pensec-'.$certificate->public_id.'.pdf');
    }
}
