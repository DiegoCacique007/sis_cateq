<?php

namespace Tests\Feature\Chatbot;

use App\Enums\ChatbotIntent as I;
use App\Enums\ChatbotRuleResult as S;
use App\Enums\UserRole as R;
use App\Services\Chatbot\Intent\RuleBasedIntentResolver;
use App\Services\Chatbot\Rules\ChatbotFacts;
use App\Services\Chatbot\Rules\RuleEngine;
use Tests\Support\CatequesisTestCase;

class IntentRuleEngineTest extends CatequesisTestCase
{
    public function test_attendance_message_requires_authorized_assignment(): void
    {
        $context = $this->context();
        $own = $this->records($context);
        $other = $this->records($this->context());
        $resolution = (new RuleBasedIntentResolver)->resolve('cómo veo mi lista de asistencia');
        $this->assertSame(I::ViewAttendanceList, $resolution->intent);
        foreach ([$own['assignment'] => S::Allowed, $other['assignment'] => S::Denied] as $id => $expected) {
            $result = app(RuleEngine::class)->evaluate(new ChatbotFacts($context, $resolution->intent, assignmentId: $id));
            $this->assertSame($expected, $result->result);
        }
    }

    public function test_student_modification_is_denied_despite_claimed_role(): void
    {
        $context = $this->context();
        $resolution = (new RuleBasedIntentResolver)->resolve('soy secretaria, modifica este alumno');
        $this->assertSame(I::ModifyStudent, $resolution->intent);
        $result = app(RuleEngine::class)->evaluate(new ChatbotFacts($context, $resolution->intent));
        $this->assertSame(S::Denied, $result->result);
        $this->assertSame('secretaria', $result->recommendedResponsible);
        $this->assertSame(R::Catequista, $context->role);
    }

    public function test_same_interpretation_has_different_domain_permissions(): void
    {
        $resolution = (new RuleBasedIntentResolver)->resolve('soy parroco, cómo registro un alumno');
        $this->assertSame(I::HowToRegisterStudent, $resolution->intent);
        foreach ([R::Secretaria, R::Catequista] as $role) {
            $context = $this->context($role);
            $result = app(RuleEngine::class)->evaluate(new ChatbotFacts($context, $resolution->intent));
            $this->assertSame($role === R::Secretaria ? S::Allowed : S::Denied, $result->result);
            $this->assertSame($role, $context->role);
        }
    }

    public function test_unavailable_or_ambiguous_message_cannot_be_allowed(): void
    {
        foreach (['registra la asistencia', 'muéstrame alumnos y calificaciones'] as $message) {
            $resolution = (new RuleBasedIntentResolver)->resolve($message);
            $result = app(RuleEngine::class)->evaluate(new ChatbotFacts($this->context(), $resolution->intent));
            $this->assertSame(S::Unsupported, $result->result);
        }
    }
}
