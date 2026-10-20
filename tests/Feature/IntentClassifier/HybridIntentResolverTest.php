<?php

namespace Tests\Feature\IntentClassifier;

use App\Enums\ChatbotIntent as I;
use App\Enums\UserRole as R;
use App\Models\User;
use App\Services\Chatbot\Contracts\IntentClassifier;
use App\Services\Chatbot\Contracts\IntentResolver;
use App\Services\Chatbot\Intent\HybridIntentResolver;
use App\Services\Chatbot\Intent\IntentClassification;
use App\Services\Chatbot\Intent\RuleBasedIntentResolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\Support\CatequesisWebTestCase;

/** Resolvedor híbrido: reglas primero; en sombra el modelo solo se registra, después de responder. */
class HybridIntentResolverTest extends CatequesisWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (require database_path('migrations/2026_10_18_000001_create_chatbot_events_table.php'))->up();
    }

    /** Clasificador falso que cuenta las llamadas y devuelve una intención fija. */
    private function fakeClassifier(?I $intent, string $outcome = 'OK'): object
    {
        $fake = new class($intent, $outcome) implements IntentClassifier
        {
            public int $calls = 0;

            public array $allowed = [];

            public function __construct(private ?I $intent, private string $outcome) {}

            public function classify(string $message, array $allowed): IntentClassification
            {
                $this->calls++;
                $this->allowed = $allowed;

                return new IntentClassification($this->intent, $this->outcome, 1234);
            }
        };
        $this->app->instance(IntentClassifier::class, $fake);

        return $fake;
    }

    private function shadow(): void
    {
        config(['chatbot.classifier.mode' => 'shadow']);
    }

    /** Prueba 5: en modo off no se hace ninguna petición HTTP, ni desde el resolvedor ni desde el endpoint. */
    public function test_off_mode_never_calls_the_classifier(): void
    {
        config(['chatbot.classifier.mode' => 'off', 'chatbot.classifier.driver' => 'ollama']);
        Http::fake();

        $this->assertInstanceOf(HybridIntentResolver::class, app(IntentResolver::class));
        $resolution = app(IntentResolver::class)->resolve('cómo van mis niños en la unidad 2');
        $this->app->terminate();
        $this->assertEquals((new RuleBasedIntentResolver)->resolve('cómo van mis niños en la unidad 2'), $resolution);

        $this->actingAs(User::findOrFail($this->context(R::Catequista)->userId))
            ->postJson(route('chatbot.message'), ['message' => 'mis grupos'])->assertOk();

        Http::assertNothingSent();
    }

    /** En sombra la respuesta es la de las reglas, y el modelo se consulta después de responder. */
    public function test_shadow_returns_the_rules_result_and_classifies_after_the_response(): void
    {
        $this->shadow();
        $fake = $this->fakeClassifier(I::ViewEvaluations);
        Log::spy();

        $resolution = app(IntentResolver::class)->resolve('cómo van mis niños en la unidad 2');

        $this->assertEquals((new RuleBasedIntentResolver)->resolve('cómo van mis niños en la unidad 2'), $resolution);
        $this->assertSame(I::Unknown, $resolution->intent);
        $this->assertSame(0, $fake->calls, 'no debe clasificar antes de terminar la petición');

        $this->app->terminate();

        $this->assertSame(1, $fake->calls);
        Log::shouldHaveReceived('info')->with('chatbot.shadow', Mockery::on(fn (array $context) => $context === [
            'rule_intent' => 'UNKNOWN', 'rule_code' => 'NO_MATCH', 'shadow_intent' => 'VIEW_EVALUATIONS',
            'shadow_outcome' => 'RESCUE', 'shadow_latency_ms' => 1200, 'prompt_version' => 'v1',
        ]))->once();
    }

    /** En el endpoint, la respuesta HTTP no cambia en sombra, aunque el modelo falle o responda vacío. */
    public function test_shadow_never_changes_the_http_response(): void
    {
        $this->actingAs(User::findOrFail($this->context(R::Catequista)->userId));
        $off = $this->postJson(route('chatbot.message'), ['message' => 'cómo van mis niños en la unidad 2'])->json();

        $this->shadow();
        foreach ([[I::ViewGroupStudents, 'OK'], [null, 'EMPTY'], [null, 'TIMEOUT']] as [$intent, $outcome]) {
            $fake = $this->fakeClassifier($intent, $outcome);
            $this->assertSame($off, $this->postJson(route('chatbot.message'), ['message' => 'cómo van mis niños en la unidad 2'])->json());
            $this->assertSame(1, $fake->calls);
        }
    }

    /** Application::terminate() no vacía sus callbacks: aunque se llame dos veces, se clasifica una sola. */
    public function test_each_message_is_classified_at_most_once(): void
    {
        $this->shadow();
        $fake = $this->fakeClassifier(I::ViewMyGroups);

        app(IntentResolver::class)->resolve('mis grupos');
        $this->app->terminate();
        $this->app->terminate();

        $this->assertSame(1, $fake->calls);
    }

    /** Prueba 7: los mensajes bloqueados por una guarda, vacíos o inválidos no se envían al modelo. */
    public function test_guarded_empty_and_invalid_messages_are_not_classified(): void
    {
        $this->shadow();
        $fake = $this->fakeClassifier(I::ViewEvaluations);

        foreach (['quiero registrar calificaciones', 'no quiero ver mis grupos', '¿?', str_repeat('a', 2001)] as $message) {
            app(IntentResolver::class)->resolve($message);
        }
        $this->app->terminate();

        $this->assertSame(0, $fake->calls);
    }

    /** Prueba 8: una consulta elegida por el modelo para un mensaje de escritura queda en UNKNOWN. */
    public function test_write_requests_never_become_read_intents(): void
    {
        $this->shadow();
        Log::spy();

        $this->fakeClassifier(I::ViewEvaluations);
        app(IntentResolver::class)->resolve('quiero cambiar la calificación de Ana');
        $this->app->terminate();
        Log::shouldHaveReceived('info')->with('chatbot.shadow', Mockery::on(fn (array $context) => $context['shadow_intent'] === 'UNKNOWN'
            && $context['shadow_outcome'] === 'AGREE'))->once();

        // Las preguntas de «cómo» con verbos de registro no se filtran.
        $this->fakeClassifier(I::HowToRegisterTutor);
        app(IntentResolver::class)->resolve('cómo registro un tutor');
        $this->app->terminate();
        Log::shouldHaveReceived('info')->with('chatbot.shadow', Mockery::on(fn (array $context) => $context['shadow_intent'] === 'HOW_TO_REGISTER_TUTOR'
            && $context['shadow_outcome'] === 'AGREE'))->once();
    }

    /** El registro de la sombra nunca contiene el texto del mensaje. */
    public function test_shadow_log_never_contains_the_message(): void
    {
        $this->shadow();
        $this->fakeClassifier(I::ViewGroupStudents);
        Log::spy();

        app(IntentResolver::class)->resolve('Ana Pérez López mis chamacos 5512345678');
        $this->app->terminate();

        Log::shouldHaveReceived('info')->with('chatbot.shadow', Mockery::on(function (array $context) {
            $flat = json_encode($context, JSON_UNESCAPED_UNICODE);

            return ! str_contains($flat, 'Ana') && ! str_contains($flat, 'chamacos') && ! str_contains($flat, '5512345678');
        }))->once();
    }

    /** Prueba 10: cualquier proveedor sirve si implementa IntentClassifier; nada más cambia. */
    public function test_any_provider_implementing_the_contract_is_used(): void
    {
        $this->shadow();
        $fake = $this->fakeClassifier(I::ViewMyGroups);

        app(IntentResolver::class)->resolve('mis grupos');
        $this->app->terminate();

        $this->assertSame(1, $fake->calls);
        $this->assertNotContains(I::ModifyStudent, $fake->allowed);
        $this->assertNotContains(I::ModifyAdministrativeData, $fake->allowed);
        $this->assertContains(I::Unknown, $fake->allowed);
    }
}
