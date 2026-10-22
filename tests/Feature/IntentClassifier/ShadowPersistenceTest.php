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

/** Prueba 6: en sombra la respuesta es la de las reglas y la comparación se guarda en chatbot_events, sin texto. */
class ShadowPersistenceTest extends CatequesisWebTestCase
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

    private function ask(string $message, string $mode): array
    {
        config(['chatbot.classifier.mode' => $mode]);

        return $this->postJson(route('chatbot.message'), ['message' => $message])->json();
    }

    #[DataProvider('comparisons')]
    public function test_shadow_keeps_the_rules_answer_and_records_the_comparison(string $message, ?I $intent, string $outcome, string $recorded, bool $shape): void
    {
        $rules = $this->ask($message, 'off');
        $this->app->instance(IntentClassifier::class, new FakeIntentClassifier($intent, $outcome));

        $this->assertSame($rules, $this->ask($message, 'shadow'));

        $event = DB::table('chatbot_events')->orderByDesc('id')->first();
        $this->assertSame($recorded, $event->classifier_outcome);
        $this->assertSame($intent?->value, $event->classifier_intent);
        $this->assertSame('v1', $event->prompt_version);
        $this->assertSame(1200, (int) $event->classifier_latency_ms);
        $this->assertSame($shape, $event->message_shape !== null, 'message_shape');
    }

    public static function comparisons(): array
    {
        return [
            // NO_MATCH ya guarda la forma (comportamiento previo); el modelo rescata.
            'rescate tras NO_MATCH' => ['cómo van mis niños en la unidad 2', I::ViewEvaluations, 'OK', 'RESCUE', true],
            'rescate tras AMBIGUOUS' => ['ver alumnos y calificaciones', I::ViewEvaluations, 'OK', 'RESCUE', true],
            'desacuerdo' => ['mis grupos', I::ViewGroupStudents, 'OK', 'DISAGREE', true],
            'acuerdo (sin forma)' => ['mis grupos', I::ViewMyGroups, 'OK', 'AGREE', false],
            'fallo del modelo' => ['mis grupos', null, 'TIMEOUT', 'TIMEOUT', false],
        ];
    }

    public function test_shadow_row_never_stores_the_message_text(): void
    {
        $this->app->instance(IntentClassifier::class, new FakeIntentClassifier(I::ViewGroupStudents));

        $this->ask('mis grupos de Ana Pérez 5512345678', 'shadow');

        $row = json_encode((array) DB::table('chatbot_events')->orderByDesc('id')->first(), JSON_UNESCAPED_UNICODE);
        foreach (['Ana', 'Pérez', 'Perez', '5512345678'] as $private) {
            $this->assertStringNotContainsString($private, $row);
        }
    }

    public function test_shape_of_disagreements_is_cleared_after_30_days_like_the_others(): void
    {
        $this->app->instance(IntentClassifier::class, new FakeIntentClassifier(I::ViewGroupStudents));
        $this->ask('mis grupos', 'shadow');
        DB::table('chatbot_events')->update(['occurred_on' => today()->subDays(31)->toDateString()]);

        \App\Models\ChatbotEvent::purgeExpired();

        $event = DB::table('chatbot_events')->first();
        $this->assertNull($event->message_shape);
        $this->assertSame('DISAGREE', $event->classifier_outcome);
    }
}
