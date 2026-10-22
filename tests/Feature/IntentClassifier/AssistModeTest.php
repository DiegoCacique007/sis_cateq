<?php

namespace Tests\Feature\IntentClassifier;

use App\Enums\ChatbotIntent as I;
use App\Enums\UserRole as R;
use App\Models\User;
use App\Services\Chatbot\Contracts\IntentClassifier;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CatequesisWebTestCase;
use Tests\Support\FakeIntentClassifier;

/**
 * Modo assist: el modelo solo se consulta con NO_MATCH o AMBIGUOUS_INTENT; su intención pasa por el filtro
 * de escritura, el motor de reglas y los agentes. Ante cualquier fallo, la respuesta es la de las reglas.
 */
class AssistModeTest extends CatequesisWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (require database_path('migrations/2026_10_18_000001_create_chatbot_events_table.php'))->up();
        (require database_path('migrations/2026_10_21_000001_add_classifier_columns_to_chatbot_events_table.php'))->up();
        $context = $this->context(R::Catequista);
        $this->records($context);
        $this->actingAs(User::findOrFail($context->userId));
    }

    private function fake(?I $intent, string $outcome = 'OK'): FakeIntentClassifier
    {
        $fake = new FakeIntentClassifier($intent, $outcome);
        $this->app->instance(IntentClassifier::class, $fake);

        return $fake;
    }

    /** Respuesta JSON del endpoint con el modo indicado. */
    private function ask(string $message, string $mode): array
    {
        config(['chatbot.classifier.mode' => $mode]);

        return $this->postJson(route('chatbot.message'), ['message' => $message])->json();
    }

    private function lastEvent(): ?object
    {
        return DB::table('chatbot_events')->orderByDesc('id')->first();
    }

    public function test_no_match_and_model_picks_evaluations_gives_the_evaluations_answer(): void
    {
        $evaluations = $this->ask('ver calificaciones', 'off');
        $escalation = $this->ask('cómo van mis niños en la unidad 2', 'off');
        $fake = $this->fake(I::ViewEvaluations);

        $assisted = $this->ask('cómo van mis niños en la unidad 2', 'assist');

        $this->assertSame($evaluations, $assisted);
        $this->assertNotSame($escalation, $assisted);
        $this->assertSame(1, $fake->calls);
        $event = $this->lastEvent();
        $this->assertSame(['VIEW_EVALUATIONS', 'NO_MATCH', 'USED', 'VIEW_EVALUATIONS', 1200, 'v1'],
            [$event->intent, $event->resolution, $event->classifier_outcome, $event->classifier_intent, (int) $event->classifier_latency_ms, $event->prompt_version]);
    }

    public function test_ambiguous_lets_the_model_choose_only_among_the_rule_candidates(): void
    {
        $evaluations = $this->ask('ver calificaciones', 'off');
        $fake = $this->fake(I::ViewEvaluations);

        $this->assertSame($evaluations, $this->ask('ver alumnos y calificaciones', 'assist'));
        $this->assertSame([I::ViewGroupStudents, I::ViewEvaluations, I::Unknown], $fake->allowed);
        $this->assertSame('AMBIGUOUS_INTENT', $this->lastEvent()->resolution);
    }

    #[DataProvider('discardedAnswers')]
    public function test_discarded_or_failed_model_answers_give_the_rules_answer(string $message, ?I $intent, string $outcome, string $recorded): void
    {
        $rules = $this->ask($message, 'off');
        $this->fake($intent, $outcome);

        $this->assertSame($rules, $this->ask($message, 'assist'));
        $event = $this->lastEvent();
        $this->assertSame($recorded, $event->classifier_outcome);
        $this->assertSame('UNKNOWN', $event->intent);
    }

    public static function discardedAnswers(): array
    {
        return [
            'intención de escritura' => ['mis calificaciones', I::ModifyStudent, 'OK', 'FALLBACK_OUT_OF_LIST'],
            'escritura administrativa' => ['mis calificaciones', I::ModifyAdministrativeData, 'OK', 'FALLBACK_OUT_OF_LIST'],
            'fuera de la lista' => ['mis calificaciones', null, 'OUT_OF_LIST', 'FALLBACK_OUT_OF_LIST'],
            'consulta para un mensaje de escritura' => ['quiero cambiar la calificación de Ana', I::ViewEvaluations, 'OK', 'FALLBACK_WRITE'],
            'el modelo dice UNKNOWN' => ['qué hora es', I::Unknown, 'OK', 'FALLBACK_UNKNOWN'],
            'tiempo agotado' => ['mis calificaciones', null, 'TIMEOUT', 'FALLBACK_TIMEOUT'],
            'error HTTP' => ['mis calificaciones', null, 'HTTP_ERROR', 'FALLBACK_HTTP_ERROR'],
            'respuesta vacía' => ['mis calificaciones', null, 'EMPTY', 'FALLBACK_EMPTY'],
            'JSON inválido' => ['mis calificaciones', null, 'INVALID_JSON', 'FALLBACK_INVALID_JSON'],
        ];
    }

    public function test_ambiguous_answer_outside_the_candidates_is_discarded(): void
    {
        $rules = $this->ask('ver alumnos y calificaciones', 'off');
        $this->fake(I::ViewMyGroups);

        $this->assertSame($rules, $this->ask('ver alumnos y calificaciones', 'assist'));
        $this->assertSame('FALLBACK_OUT_OF_LIST', $this->lastEvent()->classifier_outcome);
    }

    #[DataProvider('neverClassified')]
    public function test_guarded_resolved_empty_and_invalid_messages_never_reach_the_model(string $message): void
    {
        $fake = $this->fake(I::ViewEvaluations);

        config(['chatbot.classifier.mode' => 'assist']);
        $this->postJson(route('chatbot.message'), ['message' => $message]);

        $this->assertSame(0, $fake->calls);
        $this->assertNull($this->lastEvent()?->classifier_outcome);
    }

    public static function neverClassified(): array
    {
        return [
            'guarda de escritura' => ['quiero registrar calificaciones'],
            'guarda de negación' => ['no quiero ver mis grupos'],
            'resuelto por las reglas' => ['mis grupos'],
            'demasiado largo' => [str_repeat('a ', 1001)],
        ];
    }

    public function test_role_without_permission_gets_the_normal_rule_engine_rejection(): void
    {
        $rejection = $this->ask('cómo administro usuarios', 'off');
        $this->fake(I::HowToManageUsers);

        $assisted = $this->ask('cómo apruebo usuarios', 'assist');

        $this->assertSame($rejection, $assisted);
        $this->assertSame('USED', $this->lastEvent()->classifier_outcome);
        $this->assertNotSame($this->ask('cómo apruebo usuarios', 'off'), $assisted);
    }

    public function test_off_mode_ignores_the_classifier(): void
    {
        $fake = $this->fake(I::ViewEvaluations);
        $rules = $this->ask('cómo van mis niños en la unidad 2', 'off');

        $this->assertSame(0, $fake->calls);
        $this->assertNull($this->lastEvent()->classifier_outcome);
        $this->assertSame('UNKNOWN', $this->lastEvent()->intent);
        $this->assertArrayHasKey('status', $rules);
    }

    public function test_event_never_stores_the_message_text(): void
    {
        $this->fake(I::ViewEvaluations);

        $this->ask('cómo van los niños de Ana Pérez 5512345678', 'assist');

        $row = json_encode((array) $this->lastEvent(), JSON_UNESCAPED_UNICODE);
        foreach (['Ana', 'Pérez', 'Perez', '5512345678'] as $private) {
            $this->assertStringNotContainsString($private, $row);
        }
    }
}
