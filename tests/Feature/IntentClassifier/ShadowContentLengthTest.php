<?php

namespace Tests\Feature\IntentClassifier;

use App\Enums\ChatbotIntent as I;
use App\Enums\UserRole as R;
use App\Http\Controllers\ChatbotController;
use App\Models\User;
use App\Services\Chatbot\Contracts\IntentClassifier;
use App\Services\Chatbot\Intent\IntentClassification;
use Tests\Support\CatequesisWebTestCase;

/**
 * Con mod_php la conexión sigue abierta mientras corre la clasificación posterior a la respuesta:
 * con la sombra pendiente, la respuesta lleva Content-Length (en bytes) para que el navegador la dé por completa.
 */
class ShadowContentLengthTest extends CatequesisWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (require database_path('migrations/2026_10_18_000001_create_chatbot_events_table.php'))->up();
        $this->app->instance(IntentClassifier::class, new class implements IntentClassifier
        {
            public function classify(string $message, array $allowed): IntentClassification
            {
                return new IntentClassification(I::ViewEvaluations, 'OK');
            }
        });
        $this->actingAs(User::findOrFail($this->context(R::Catequista)->userId));
    }

    public function test_pending_shadow_classification_adds_content_length_in_bytes(): void
    {
        config(['chatbot.classifier.mode' => 'shadow']);

        $response = $this->postJson(route('chatbot.message'), ['message' => 'cómo van mis niños en la unidad 2'])->assertOk();

        $response->assertHeader('Content-Length', (string) strlen($response->getContent()));
    }

    public function test_no_content_length_without_pending_shadow_classification(): void
    {
        config(['chatbot.classifier.mode' => 'shadow']);
        // Bloqueado por una guarda: no hay clasificación pendiente.
        $this->postJson(route('chatbot.message'), ['message' => 'quiero registrar calificaciones'])
            ->assertOk()->assertHeaderMissing('Content-Length');
    }

    public function test_off_mode_response_is_unchanged(): void
    {
        config(['chatbot.classifier.mode' => 'off']);

        $this->postJson(route('chatbot.message'), ['message' => 'cómo van mis niños en la unidad 2'])
            ->assertOk()->assertHeaderMissing('Content-Length');
    }

    public function test_length_is_counted_in_bytes_not_characters(): void
    {
        $this->assertSame('7', ChatbotController::contentLength('ñandú'));
        $this->assertSame('0', ChatbotController::contentLength(''));
    }
}
