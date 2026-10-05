<?php

namespace Tests\Unit\Chatbot;

use App\Enums\CatequesisCapability as C;
use App\Enums\ChatbotIntent as I;
use App\Enums\ChatbotRuleResult as S;
use App\Enums\UserRole as R;
use App\Services\Authorization\AccessContext;
use App\Services\Authorization\CatequesisAccess;
use App\Services\Chatbot\Rules\ChatbotFacts;
use App\Services\Chatbot\Rules\RuleCatalog;
use App\Services\Chatbot\Rules\RuleEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RuleEngineTest extends TestCase
{
    private function engine(): RuleEngine
    {
        return new RuleEngine(new CatequesisAccess, new RuleCatalog);
    }

    private function context(R $role = R::Catequista, ?int $period = 1, ?int $community = 1): AccessContext
    {
        return new AccessContext(1, $role, 'aprobado', $community, $period);
    }

    public function test_unknown_intent_is_unsupported_with_human_recommendation(): void
    {
        $result = $this->engine()->evaluate(new ChatbotFacts($this->context(), I::Unknown));
        $this->assertSame(S::Unsupported, $result->result);
        $this->assertSame('UNKNOWN_INTENT', $result->code);
        $this->assertSame('system_human', $result->recommendedResponsible);
        $this->assertSame(['AUTH-001', 'INT-001', 'ESC-002'], $result->matchedRules);
    }

    public static function invalidContexts(): array
    {
        return [[0, 'aprobado'], [1, 'pendiente'], [1, 'bloqueado']];
    }

    #[DataProvider('invalidContexts')]
    public function test_security_precedes_help_unknown_and_functional_rules(int $id, string $status): void
    {
        foreach (I::cases() as $intent) {
            $result = $this->engine()->evaluate(new ChatbotFacts(new AccessContext($id, R::Secretaria, $status, 1, 1), $intent));
            $this->assertSame(S::Denied, $result->result);
            $this->assertSame(['AUTH-002'], $result->matchedRules);
        }
    }

    public static function academicIntents(): array
    {
        return array_map(fn (I $i) => [$i], [I::ViewMyGroups, I::ViewGroupStudents, I::ViewAttendanceList, I::ViewEvaluations, I::HowToRecordEvaluations]);
    }

    #[DataProvider('academicIntents')]
    public function test_academic_intent_requires_period(I $intent): void
    {
        $result = $this->engine()->evaluate(new ChatbotFacts($this->context(period: null), $intent));
        $this->assertSame(S::MissingContext, $result->result);
        $this->assertSame('MISSING_PERIOD', $result->code);
        $this->assertContains('CTX-001', $result->matchedRules);
    }

    public function test_help_does_not_invent_a_capability_or_require_period(): void
    {
        $result = $this->engine()->evaluate(new ChatbotFacts($this->context(period: null), I::HelpSystem));
        $this->assertSame(S::Allowed, $result->result);
        $this->assertNull($result->capability);
        $this->assertContains('HELP-001', $result->matchedRules);
    }

    public function test_catechist_can_request_own_groups(): void
    {
        $result = $this->engine()->evaluate(new ChatbotFacts($this->context(), I::ViewMyGroups));
        $this->assertSame(S::Allowed, $result->result);
        $this->assertSame(C::ViewGroups, $result->capability);
        $this->assertSame(['AUTH-001', 'CAP-001', 'CAT-001', 'CAT-002'], $result->matchedRules);
    }

    public function test_catechist_can_request_evaluation_guidance(): void
    {
        $result = $this->engine()->evaluate(new ChatbotFacts($this->context(), I::HowToRecordEvaluations));
        $this->assertSame(S::Allowed, $result->result);
        $this->assertSame(C::ManageEvaluations, $result->capability);
        $this->assertContains('CAT-006', $result->matchedRules);
    }

    public function test_catechist_student_modification_is_denied_and_recommends_secretary(): void
    {
        $result = $this->engine()->evaluate(new ChatbotFacts($this->context(), I::ModifyStudent, studentId: 42));
        $this->assertSame(S::Denied, $result->result);
        $this->assertSame('ROLE_NOT_ALLOWED', $result->code);
        $this->assertSame('secretaria', $result->recommendedResponsible);
        $this->assertContains('CAT-007', $result->matchedRules);
        $this->assertContains('ESC-001', $result->matchedRules);
        $this->assertSame([], $result->context);
    }

    public static function secretaryGuidance(): array
    {
        return [
            [I::HowToRegisterStudent, C::ManageStudents, 'SEC-001'],
            [I::HowToRegisterInscription, C::ManageInscriptions, 'SEC-002'],
            [I::HowToAssignGroup, C::ManageAssignments, 'SEC-003'],
            [I::HowToManageUsers, C::ManageUsers, 'SEC-004'],
            [I::HowToRegisterTutor, C::ManageTutors, 'SEC-005'],
            [I::HowToManageCommunities, C::ManageCommunities, 'SEC-006'],
            [I::HowToManagePeriods, C::ManagePeriods, 'SEC-007'],
            [I::HowToManageLevels, C::ManageLevels, 'SEC-008'],
            [I::HowToManageUnits, C::ManageUnits, 'SEC-009'],
            [I::HowToManageRubrics, C::ManageRubrics, 'SEC-010'],
            [I::HowToRecordEvaluations, C::ManageEvaluations, 'SEC-011'],
        ];
    }

    #[DataProvider('secretaryGuidance')]
    public function test_secretary_guidance(I $intent, C $capability, string $rule): void
    {
        $result = $this->engine()->evaluate(new ChatbotFacts($this->context(R::Secretaria), $intent));
        $this->assertSame(S::Allowed, $result->result);
        $this->assertSame($capability, $result->capability);
        $this->assertContains($rule, $result->matchedRules);
    }

    public static function supervisors(): array
    {
        return [[R::Parroco, 'PAR'], [R::CoordinadorGeneral, 'CG'], [R::CoordinadorComunidades, 'CC']];
    }

    #[DataProvider('supervisors')]
    public function test_supervisors_have_read_decisions_but_no_administrative_grant(R $role, string $prefix): void
    {
        $result = $this->engine()->evaluate(new ChatbotFacts($this->context($role), I::ViewEvaluations));
        $this->assertSame(S::Allowed, $result->result);
        $this->assertContains($prefix.'-001', $result->matchedRules);
        foreach ([I::HowToManageUsers, I::HowToRegisterStudent, I::ModifyStudent] as $intent) {
            $denied = $this->engine()->evaluate(new ChatbotFacts($this->context($role), $intent));
            $this->assertSame(S::Denied, $denied->result);
            $this->assertSame('secretaria', $denied->recommendedResponsible);
            $this->assertContains($prefix.($role === R::CoordinadorComunidades ? '-003' : '-002'), $denied->matchedRules);
        }
    }

    public function test_community_context_is_required(): void
    {
        $result = $this->engine()->evaluate(new ChatbotFacts($this->context(R::CoordinadorComunidades, community: null), I::ViewEvaluations));
        $this->assertSame(S::MissingContext, $result->result);
        $this->assertSame('MISSING_COMMUNITY', $result->code);
        $this->assertContains('CTX-002', $result->matchedRules);
    }

    public function test_administrative_execution_is_never_allowed_even_to_secretary(): void
    {
        $result = $this->engine()->evaluate(new ChatbotFacts($this->context(R::Secretaria), I::ModifyStudent));
        $this->assertSame(S::Unsupported, $result->result);
        foreach (R::cases() as $role) {
            $result = $this->engine()->evaluate(new ChatbotFacts($this->context($role), I::ModifyAdministrativeData));
            $this->assertSame(S::Escalate, $result->result);
            $this->assertSame('secretaria', $result->recommendedResponsible);
        }
    }

    public function test_assignment_must_be_selected_for_students_and_attendance(): void
    {
        foreach ([I::ViewGroupStudents, I::ViewAttendanceList] as $intent) {
            $result = $this->engine()->evaluate(new ChatbotFacts($this->context(), $intent));
            $this->assertSame(S::MissingContext, $result->result);
            $this->assertSame(['required' => ['assignmentId']], $result->context);
        }
    }

    public function test_invalid_ids_and_unsupported_selectors_are_not_ignored(): void
    {
        $invalid = $this->engine()->evaluate(new ChatbotFacts($this->context(), I::ViewGroupStudents, assignmentId: 0));
        $this->assertSame(S::Denied, $invalid->result);
        $this->assertSame('INVALID_RESOURCE_ID', $invalid->code);
        $unsupported = $this->engine()->evaluate(new ChatbotFacts($this->context(), I::HelpSystem, evaluationId: 5));
        $this->assertSame(S::Unsupported, $unsupported->result);
        $this->assertSame('RESOURCE_SELECTION_UNSUPPORTED', $unsupported->code);
        $combined = $this->engine()->evaluate(new ChatbotFacts($this->context(), I::ViewEvaluations, studentId: 5, evaluationId: 6));
        $this->assertSame(S::Unsupported, $combined->result);
    }

    public function test_no_intent_can_bypass_a_negative_capability_decision(): void
    {
        $catalog = new RuleCatalog;
        $access = new CatequesisAccess;
        foreach (R::cases() as $role) {
            foreach (I::cases() as $intent) {
                $capability = $catalog->capability($intent);
                if ($capability === null) {
                    continue;
                }
                $context = $this->context($role);
                $decision = $access->can($context, $capability);
                if (! $decision->isAllowed()) {
                    $result = $this->engine()->evaluate(new ChatbotFacts($context, $intent));
                    $this->assertSame(S::Denied, $result->result, $role->value.':'.$intent->value);
                    $this->assertSame($decision->code, $result->code);
                }
            }
        }
    }

    public function test_capability_denial_precedes_missing_period_as_in_domain_authority(): void
    {
        $result = $this->engine()->evaluate(new ChatbotFacts($this->context(R::Secretaria, period: null), I::ViewAttendanceList));
        $this->assertSame(S::Denied, $result->result);
        $this->assertSame('ROLE_NOT_ALLOWED', $result->code);
    }

    public function test_unknown_role_cannot_be_represented_by_the_typed_context(): void
    {
        $this->expectException(\TypeError::class);
        new AccessContext(1, 'admin', 'aprobado', 1, 1);
    }

    public function test_facts_and_context_are_immutable(): void
    {
        $facts = new ChatbotFacts($this->context(), I::ViewMyGroups);
        $this->expectException(\Error::class);
        $facts->context->userId = 99;
    }
}
