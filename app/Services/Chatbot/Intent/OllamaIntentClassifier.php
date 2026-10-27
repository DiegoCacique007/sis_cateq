<?php


namespace App\Services\Chatbot\Intent;

use App\Enums\ChatbotIntent;
use App\Services\Chatbot\Contracts\IntentClassifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

final class OllamaIntentClassifier implements IntentClassifier
{
    public function __construct(
        private readonly string $url,
        private readonly string $model,
        private readonly int $timeout = 30,
        private readonly string $keepAlive = '30m',
        private readonly IntentDescriptions $descriptions = new IntentDescriptions,
    ) {}

    public function classify(string $message, array $allowed): IntentClassification
    {
        $start = hrtime(true);
        $elapsed = fn () => (int) round((hrtime(true) - $start) / 1_000_000);
        $values = array_map(fn (ChatbotIntent $intent) => $intent->value, $allowed);

        if ($values === []) return IntentClassification::failed('OUT_OF_LIST', $elapsed());

        try {
            $response = Http::connectTimeout(5)->timeout($this->timeout)->acceptJson()
                ->post(rtrim($this->url, '/').'/api/chat', [
                    'model' => $this->model,
                    'stream' => false,
                    'think' => false,
                    'keep_alive' => $this->keepAlive,
                    'format' => [
                        'type' => 'object',
                        'properties' => [
                            'intent' => ['type' => 'string', 'enum' => $values]
                        ],
                        'required' => ['intent'],
                        'additionalProperties' => false
                    ],
                    'options' => [
                        'temperature' => 0,
                        'num_predict' => 100,
                        'num_ctx' => 4096
                    ],
                    'messages' => [
                        ['role' => 'system', 'content' => $this->descriptions->systemPrompt($allowed)],
                        ['role' => 'user', 'content' => $message]
                    ]
                ]);
        } catch (ConnectionException $error) {
            $reason = str_contains(strtolower($error->getMessage()), 'timed out') ? 'TIMEOUT' : 'HTTP_ERROR';
            return IntentClassification::failed($reason, $elapsed());
        } catch (Throwable) {
            return IntentClassification::failed('HTTP_ERROR', $elapsed());
        }

        if (!$response->successful()) return IntentClassification::failed('HTTP_ERROR', $elapsed());

        $content = $response->json('message.content');
        if (!is_string($content) || trim($content) === '') {
            return IntentClassification::failed('EMPTY', $elapsed());
        }

        $decoded = json_decode(trim($content), true);
        if (!is_array($decoded) || array_keys($decoded) !== ['intent']) {
            return IntentClassification::failed('INVALID_JSON', $elapsed());
        }

        $intent = is_string($decoded['intent']) ? ChatbotIntent::tryFrom($decoded['intent']) : null;
        if ($intent === null || !in_array($intent, $allowed, true)) {
            return IntentClassification::failed('OUT_OF_LIST', $elapsed());
        }

        return new IntentClassification($intent, 'OK', $elapsed());
    }
}
