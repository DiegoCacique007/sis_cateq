<?php

namespace Tests\Feature\IntentClassifier;

use App\Enums\ChatbotIntent as I;
use App\Services\Chatbot\Contracts\IntentClassifier;
use App\Services\Chatbot\Intent\IntentDescriptions;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakeIntentClassifier;
use Tests\TestCase;

/** chatbot:report con datos del clasificador, chatbot:evaluate-classifier (sin red) y el conjunto de frases. */
class ClassifierToolsTest extends TestCase
{
    private const DATASET = 'tests/Fixtures/chatbot_frases.php';

    public function test_report_summarizes_classifier_columns(): void
    {
        (require database_path('migrations/2026_10_18_000001_create_chatbot_events_table.php'))->up();
        (require database_path('migrations/2026_10_21_000001_add_classifier_columns_to_chatbot_events_table.php'))->up();
        $row = fn (array $values) => array_merge(['occurred_on' => today()->toDateString(), 'role' => 'catequista', 'intent' => 'UNKNOWN',
            'resolution' => 'NO_MATCH', 'status' => 'success', 'code' => 'X', 'with_selection' => false, 'message_shape' => null,
            'classifier_intent' => null, 'classifier_outcome' => null, 'classifier_latency_ms' => null, 'prompt_version' => 'v1'], $values);
        DB::table('chatbot_events')->insert([
            $row(['intent' => 'VIEW_EVALUATIONS', 'classifier_outcome' => 'USED', 'classifier_intent' => 'VIEW_EVALUATIONS', 'classifier_latency_ms' => 900, 'message_shape' => 'como van mis …']),
            $row(['intent' => 'VIEW_GROUP_STUDENTS', 'classifier_outcome' => 'USED', 'classifier_intent' => 'VIEW_GROUP_STUDENTS', 'classifier_latency_ms' => 1100]),
            $row(['classifier_outcome' => 'FALLBACK_TIMEOUT', 'classifier_latency_ms' => 4000]),
            $row(['intent' => 'VIEW_MY_GROUPS', 'resolution' => 'INTENT_RESOLVED', 'classifier_outcome' => 'AGREE', 'classifier_intent' => 'VIEW_MY_GROUPS', 'classifier_latency_ms' => 800]),
            $row(['intent' => 'VIEW_MY_GROUPS', 'resolution' => 'INTENT_RESOLVED', 'classifier_outcome' => 'DISAGREE', 'classifier_intent' => 'VIEW_GROUP_STUDENTS', 'classifier_latency_ms' => 800, 'message_shape' => 'mis grupos']),
            $row(['classifier_outcome' => 'RESCUE', 'classifier_intent' => 'VIEW_EVALUATIONS', 'classifier_latency_ms' => 1000]),
            $row(['classifier_outcome' => null, 'prompt_version' => null]),
        ]);

        $this->artisan('chatbot:report', ['--days' => 30])
            ->expectsOutputToContain('Clasificador: 6 eventos con modelo (prompt: v1).')
            ->expectsOutputToContain('Modo assist: respuesta del modelo en 2 de 3 consultas (66.7 %); 1 vueltas a las reglas.')
            ->expectsOutputToContain('Modo sombra: 1 acuerdos, 1 desacuerdos, 1 rescates, 0 fallos.')
            ->expectsOutputToContain('Latencia del modelo: media 1433 ms, p95 4000 ms, máxima 4000 ms.')
            ->expectsOutputToContain('FALLBACK_TIMEOUT')
            ->expectsOutputToContain('como van mis …')
            ->assertExitCode(0);
    }

    public function test_evaluation_reports_total_and_per_intent_accuracy_and_errors(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'frases').'.php';
        file_put_contents($file, "<?php\nreturn [\n"
            ."['frase' => 'cómo van mis chavos', 'esperada' => 'VIEW_EVALUATIONS'],\n"
            ."['frase' => 'calificaciones de la unidad 2', 'esperada' => 'VIEW_EVALUATIONS'],\n"
            ."['frase' => 'lista de mis chamacos', 'esperada' => 'VIEW_GROUP_STUDENTS'],\n"
            ."['frase' => 'mis grupos', 'esperada' => 'VIEW_MY_GROUPS'],\n"
            ."];\n");
        $this->app->instance(IntentClassifier::class, new FakeIntentClassifier(I::ViewEvaluations, latencyMs: 700));

        try {
            $this->artisan('chatbot:evaluate-classifier', ['--file' => $file])
                // La fila de errores contiene también VIEW_GROUP_STUDENTS: se declara antes para que sea suya.
                ->expectsOutputToContain('lista de mis chamacos')
                ->expectsOutputToContain('4 frases')
                ->expectsOutputToContain('Aciertos del modelo: 2/4 (50 %).')
                // assist: «mis grupos» lo resuelven las reglas; el resto, el modelo.
                ->expectsOutputToContain('Aciertos en modo assist (reglas + modelo): 3/4 (75 %).')
                ->expectsOutputToContain('VIEW_GROUP_STUDENTS')
                ->expectsOutputToContain('Latencia del modelo: media 700 ms')
                ->assertExitCode(0);
        } finally {
            @unlink($file);
        }
    }

    public function test_evaluation_requires_a_configured_provider(): void
    {
        config(['chatbot.classifier.driver' => 'none']);

        $this->artisan('chatbot:evaluate-classifier')
            ->expectsOutputToContain('CHATBOT_CLASSIFIER_DRIVER')
            ->assertExitCode(1);
    }

    public function test_dataset_is_valid_and_covers_colloquial_and_doubtful_cases(): void
    {
        $phrases = require base_path(self::DATASET);
        $offered = array_map(fn (I $intent) => $intent->value, (new IntentDescriptions)->offered());

        $this->assertGreaterThanOrEqual(50, count($phrases));
        foreach ($phrases as $phrase) {
            $this->assertNotSame('', trim($phrase['frase']));
            $this->assertContains($phrase['esperada'], $offered, $phrase['frase']);
        }
        $texts = implode(' | ', array_column($phrases, 'frase'));
        foreach (['mis chamacos', 'mis niños', 'cómo van mis chavos'] as $colloquial) {
            $this->assertStringContainsString($colloquial, $texts);
        }
        // Cada intención ofrecida tiene al menos dos frases, y hay casos dudosos entre vecinas.
        $counts = array_count_values(array_column($phrases, 'esperada'));
        foreach ($offered as $intent) {
            $this->assertGreaterThanOrEqual(2, $counts[$intent] ?? 0, $intent);
        }
        $this->assertGreaterThanOrEqual(10, count(array_filter($phrases, fn (array $phrase) => isset($phrase['dudoso']))));
        // Nada de datos reales: ni números de teléfono ni correos.
        $this->assertDoesNotMatchRegularExpression('/\d{7,}|@/', $texts);
    }
}
