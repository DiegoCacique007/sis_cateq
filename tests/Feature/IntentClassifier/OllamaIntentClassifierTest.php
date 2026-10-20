<?php

namespace Tests\Feature\IntentClassifier;

use App\Enums\ChatbotIntent as I;
use App\Services\Chatbot\Intent\IntentDescriptions;
use App\Services\Chatbot\Intent\OllamaIntentClassifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Adaptador de Ollama: petición exacta, lectura del campo de respuesta y fallos que vuelven a las reglas. Sin red. */
class OllamaIntentClassifierTest extends TestCase
{
    private const URL = 'http://localhost:11434';

    private const MODEL = 'hf.co/Mungert/Qwen3-4B-Instruct-2507-GGUF:Q4_K_M';

    private function classifier(): OllamaIntentClassifier
    {
        return new OllamaIntentClassifier(self::URL, self::MODEL, timeout: 8, keepAlive: '10m');
    }

    private function reply(string $content, ?string $thinking = null): void
    {
        $message = ['role' => 'assistant', 'content' => $content];
        if ($thinking !== null) {
            $message['thinking'] = $thinking;
        }
        Http::fake([self::URL.'/api/chat' => Http::response(['model' => self::MODEL, 'message' => $message, 'done' => true])]);
    }

    /** Prueba 1: la petición desactiva el razonamiento y limita la salida a la lista cerrada. */
    public function test_request_disables_thinking_and_constrains_the_output(): void
    {
        $this->reply('{"intent":"VIEW_EVALUATIONS"}');
        $offered = (new IntentDescriptions)->offered();

        $result = $this->classifier()->classify('cómo van mis niños en la unidad 2', $offered);

        $this->assertSame('OK', $result->outcome);
        $this->assertSame(I::ViewEvaluations, $result->intent);
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($offered) {
            $body = $request->data();

            return $request->url() === self::URL.'/api/chat'
                && $request->method() === 'POST'
                && $body['model'] === self::MODEL
                && $body['think'] === false
                && $body['stream'] === false
                && $body['keep_alive'] === '10m'
                && $body['options']['temperature'] === 0
                && $body['format']['properties']['intent']['enum'] === array_map(fn (I $intent) => $intent->value, $offered)
                && $body['format']['required'] === ['intent']
                && $body['messages'][0]['role'] === 'system'
                && str_contains($body['messages'][0]['content'], 'VIEW_EVALUATIONS')
                && $body['messages'][1] === ['role' => 'user', 'content' => 'cómo van mis niños en la unidad 2']
                && count($body['messages']) === 2;
        });
    }

    /** Prueba 2: respuesta vacía, aunque venga el campo de razonamiento, es un fallo. */
    #[DataProvider('emptyContents')]
    public function test_empty_content_is_a_failure_even_with_thinking(string $content): void
    {
        $this->reply($content, thinking: '{"intent":"VIEW_EVALUATIONS"}');

        $result = $this->classifier()->classify('cómo van mis niños', (new IntentDescriptions)->offered());

        $this->assertSame('EMPTY', $result->outcome);
        $this->assertNull($result->intent);
    }

    public static function emptyContents(): array
    {
        return ['vacío' => [''], 'solo espacios' => ["  \n "]];
    }

    /** Prueba 3: JSON inválido, texto extra, intención fuera de la lista o de escritura se descartan. */
    #[DataProvider('invalidContents')]
    public function test_invalid_or_out_of_list_answers_are_discarded(string $content, string $outcome): void
    {
        $this->reply($content);

        $result = $this->classifier()->classify('mis alumnos', (new IntentDescriptions)->offered());

        $this->assertSame($outcome, $result->outcome);
        $this->assertNull($result->intent);
    }

    public static function invalidContents(): array
    {
        return [
            'texto' => ['VIEW_EVALUATIONS', 'INVALID_JSON'],
            'texto extra' => ['Claro: {"intent":"VIEW_EVALUATIONS"}', 'INVALID_JSON'],
            'campo extra' => ['{"intent":"VIEW_EVALUATIONS","confidence":0.9}', 'INVALID_JSON'],
            'lista' => ['["VIEW_EVALUATIONS"]', 'INVALID_JSON'],
            'no es texto' => ['{"intent":5}', 'OUT_OF_LIST'],
            'inventada' => ['{"intent":"BORRAR_TODO"}', 'OUT_OF_LIST'],
            'escritura' => ['{"intent":"MODIFY_STUDENT"}', 'OUT_OF_LIST'],
            'escritura administrativa' => ['{"intent":"MODIFY_ADMINISTRATIVE_DATA"}', 'OUT_OF_LIST'],
        ];
    }

    /** Prueba 4: tiempo agotado o error HTTP no lanzan excepciones. */
    #[DataProvider('transportFailures')]
    public function test_timeouts_and_http_errors_never_throw(string $case, string $outcome): void
    {
        match ($case) {
            'timeout' => Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out after 8001 milliseconds')),
            'refused' => Http::fake(fn () => throw new ConnectionException('cURL error 7: Failed to connect to localhost port 11434: Connection refused')),
            'not_found' => Http::fake([self::URL.'/api/chat' => Http::response(['error' => 'model not found'], 404)]),
            'server_error' => Http::fake([self::URL.'/api/chat' => Http::response('error', 500)]),
            'not_json' => Http::fake([self::URL.'/api/chat' => Http::response('no es json', 200)]),
        };

        $result = $this->classifier()->classify('mis grupos', (new IntentDescriptions)->offered());

        $this->assertSame($outcome, $result->outcome);
        $this->assertNull($result->intent);
    }

    public static function transportFailures(): array
    {
        return [
            'tiempo agotado' => ['timeout', 'TIMEOUT'],
            'conexión rechazada' => ['refused', 'HTTP_ERROR'],
            'modelo no encontrado (404)' => ['not_found', 'HTTP_ERROR'],
            'error del servidor (500)' => ['server_error', 'HTTP_ERROR'],
            'respuesta que no es JSON' => ['not_json', 'EMPTY'],
        ];
    }
}
