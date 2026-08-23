<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The one place a request reaches DeepSeek.
 *
 * Two settings here are not preferences, they are scars:
 *
 * - **No `max_tokens`.** The v4 models think before they answer and those
 *   thinking tokens count against the same limit. A cap large enough for the
 *   prose is still small enough to be eaten by reasoning, and the answer comes
 *   back empty or cut in half with no error at all. `finish_reason` is logged
 *   below so a truncated answer is at least visible.
 * - **`reasoning_effort`.** Left alone, the flash model reasons at full tilt:
 *   far slower, and thousands of thinking tokens billed per report for no gain
 *   on a writing task.
 */
class DeepSeek
{
    /**
     * @return array{ok: bool, content?: string, model?: string, input_tokens?: int, output_tokens?: int, finish_reason?: ?string, error?: string}
     */
    public function write(string $system, string $prompt): array
    {
        $key = config('services.deepseek.key');

        if (blank($key)) {
            return ['ok' => false, 'error' => 'Brak klucza API DeepSeek.'];
        }

        try {
            $response = Http::withToken($key)
                ->acceptJson()
                ->timeout((int) config('services.deepseek.timeout'))
                ->retry(2, 2000, throw: false)
                ->post(rtrim((string) config('services.deepseek.base_url'), '/').'/chat/completions', [
                    'model' => config('services.deepseek.model'),
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => (float) config('services.deepseek.temperature'),
                    'reasoning_effort' => config('services.deepseek.reasoning_effort'),
                ]);
        } catch (Throwable $exception) {
            Log::error('DeepSeek request failed', ['error' => $exception->getMessage()]);

            return ['ok' => false, 'error' => 'Nie udało się połączyć z DeepSeek.'];
        }

        if (! $response->successful()) {
            Log::error('DeepSeek returned an error', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 1000),
            ]);

            return ['ok' => false, 'error' => "DeepSeek odpowiedział błędem HTTP {$response->status()}."];
        }

        $content = $response->json('choices.0.message.content');
        $finishReason = $response->json('choices.0.finish_reason');

        Log::info('DeepSeek response', [
            'model' => $response->json('model'),
            'input_tokens' => $response->json('usage.prompt_tokens'),
            'output_tokens' => $response->json('usage.completion_tokens'),
            'reasoning_tokens' => $response->json('usage.completion_tokens_details.reasoning_tokens'),
            'finish_reason' => $finishReason,
        ]);

        if ($finishReason === 'length') {
            Log::warning('DeepSeek answer was cut short by the token limit');
        }

        if (! is_string($content) || trim($content) === '') {
            return ['ok' => false, 'error' => 'DeepSeek zwrócił pustą odpowiedź.'];
        }

        return [
            'ok' => true,
            'content' => trim($content),
            'model' => (string) ($response->json('model') ?? config('services.deepseek.model')),
            'input_tokens' => (int) ($response->json('usage.prompt_tokens') ?? 0),
            'output_tokens' => (int) ($response->json('usage.completion_tokens') ?? 0),
            'finish_reason' => is_string($finishReason) ? $finishReason : null,
        ];
    }
}
