<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiService
{
    public function isConfigured(): bool
    {
        return filled(config('services.ai.key')) && filled(config('services.ai.base_url'));
    }

    public function complete(string $system, string $prompt, int $maxTokens = 600): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('ИИ не настроен: нет AI_API_KEY.');
        }

        $response = Http::withToken(config('services.ai.key'))
            ->timeout(40)
            ->post(rtrim(config('services.ai.base_url'), '/').'/chat/completions', [
                'model'      => config('services.ai.model'),
                // модели с «рассуждением» тратят часть лимита на размышления, поэтому запас
                'max_tokens' => $maxTokens * 4,
                'messages'   => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Ошибка ИИ-сервиса, код '.$response->status());
        }

        $text = trim((string) $response->json('choices.0.message.content'));

        if ($text === '') {
            throw new RuntimeException('ИИ вернул пустой ответ.');
        }

        return $text;
    }
}