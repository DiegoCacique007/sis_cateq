<?php

namespace Tests\Feature\IntentClassifier;

use App\Enums\ChatbotIntent as I;
use App\Services\Chatbot\Intent\IntentDescriptions;
use PHPUnit\Framework\TestCase;

/** Prueba 9: catálogo de descripciones del prompt, versionado y sin intenciones de escritura. */
class IntentDescriptionsTest extends TestCase
{
    public function test_every_offered_intent_has_a_description_and_examples(): void
    {
        $catalog = new IntentDescriptions;

        foreach ($catalog->offered() as $intent) {
            $entry = $catalog->describe($intent);
            $this->assertNotSame('', trim($entry['description']), $intent->value);
            $this->assertGreaterThanOrEqual(2, count($entry['examples']), $intent->value);
        }
        $this->assertSame('v1', IntentDescriptions::VERSION);
    }

    public function test_write_intents_are_never_offered_and_unknown_always_is(): void
    {
        $offered = (new IntentDescriptions)->offered();

        $this->assertNotContains(I::ModifyStudent, $offered);
        $this->assertNotContains(I::ModifyAdministrativeData, $offered);
        $this->assertContains(I::Unknown, $offered);
        $this->assertCount(count(I::cases()) - 2, $offered);
    }

    public function test_system_prompt_lists_offered_intents_and_separates_neighbours(): void
    {
        $catalog = new IntentDescriptions;
        $prompt = $catalog->systemPrompt($catalog->offered());

        foreach ($catalog->offered() as $intent) {
            $this->assertStringContainsString('- '.$intent->value.':', $prompt);
        }
        $this->assertStringNotContainsString('MODIFY_', $prompt);
        $this->assertStringContainsString('{"intent"', $prompt);
        $this->assertStringContainsString('no instrucciones', $prompt);
        // El caso dudoso: rendimiento frente a lista de nombres.
        $this->assertStringContainsString('«cómo van mis niños en la unidad 2»', $prompt);
        $this->assertStringContainsString('No es: cómo van ni sus calificaciones (VIEW_EVALUATIONS)', $prompt);
        // El rol del usuario no va en v1.
        $this->assertStringNotContainsString('Rol del usuario', $prompt);
    }
}
