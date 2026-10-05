<?php

namespace Tests\Feature\Chatbot;

use App\Enums\ChatbotIntent as I;
use App\Enums\ChatbotRuleResult as S;
use App\Enums\UserRole as R;
use App\Models\User;
use App\Services\Authorization\AccessContextResolver;
use App\Services\Chatbot\Rules\ChatbotFacts;
use App\Services\Chatbot\Rules\RuleEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CatequesisTestCase;

class RuleEngineScopeTest extends CatequesisTestCase
{
    public static function assignmentIntents(): array
    {
        return [[I::ViewGroupStudents, 'CAT-003'], [I::ViewAttendanceList, 'CAT-005']];
    }

    #[DataProvider('assignmentIntents')]
    public function test_own_assignment_and_explainable_result(I $intent, string $rule): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        $result = app(RuleEngine::class)->evaluate(new ChatbotFacts($context, $intent, assignmentId: $ids['assignment']));
        $this->assertSame(S::Allowed, $result->result);
        $this->assertSame(['AUTH-001', 'CAP-001', 'RES-001', 'CAT-001', $rule], $result->matchedRules);
        $this->assertSame([], $result->context);
    }

    public function test_foreign_and_nonexistent_assignments_are_indistinguishable(): void
    {
        $context = $this->context();
        $foreign = $this->records($this->context());
        foreach ([$foreign['assignment'], 99999] as $id) {
            $result = app(RuleEngine::class)->evaluate(new ChatbotFacts($context, I::ViewGroupStudents, assignmentId: $id));
            $this->assertSame(S::Denied, $result->result);
            $this->assertSame('OUTSIDE_SCOPE', $result->code);
            $this->assertContains('CAT-004', $result->matchedRules);
            $this->assertSame([], $result->context);
        }
    }

    public function test_own_assignment_in_other_period_is_denied(): void
    {
        $context = $this->context();
        $ids = $this->records($context, period: 2);
        $result = app(RuleEngine::class)->evaluate(new ChatbotFacts($context, I::ViewGroupStudents, assignmentId: $ids['assignment']));
        $this->assertSame(S::Denied, $result->result);
        $this->assertSame('OUTSIDE_SCOPE', $result->code);
    }

    public function test_community_scope_is_not_replaced_by_selected_assignment(): void
    {
        $teacher = $this->context();
        $own = $this->records($teacher);
        $foreign = $this->records($teacher, community: 2);
        $context = $this->context(R::CoordinadorComunidades);
        $allowed = app(RuleEngine::class)->evaluate(new ChatbotFacts($context, I::ViewGroupStudents, assignmentId: $own['assignment']));
        $denied = app(RuleEngine::class)->evaluate(new ChatbotFacts($context, I::ViewGroupStudents, assignmentId: $foreign['assignment']));
        $this->assertSame(S::Allowed, $allowed->result);
        $this->assertContains('CC-001', $allowed->matchedRules);
        $this->assertSame(S::Denied, $denied->result);
        $this->assertContains('CC-002', $denied->matchedRules);
    }

    public function test_ambiguous_assignment_is_not_resolved_by_explicit_id(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        DB::table('asigna_grupo')->insert([
            'catequista_id' => $this->context()->userId, 'comunidad_id' => 2,
            'grupo_id' => $ids['group'], 'nivel_id' => 2, 'periodo_id' => 1,
        ]);
        foreach ([I::ViewGroupStudents, I::ViewAttendanceList, I::HowToRecordEvaluations] as $intent) {
            $result = app(RuleEngine::class)->evaluate(new ChatbotFacts($context, $intent, assignmentId: $ids['assignment']));
            $this->assertSame(S::Ambiguous, $result->result);
            $this->assertSame('AMBIGUOUS_ASSIGNMENT', $result->code);
            $this->assertContains('AMB-001', $result->matchedRules);
        }
    }

    public static function evaluationSelectors(): array
    {
        return [['assignmentId', 'assignment'], ['inscriptionId', 'inscription'], ['studentId', 'student'], ['evaluationId', 'evaluation']];
    }

    #[DataProvider('evaluationSelectors')]
    public function test_each_evaluation_selector_is_authorized(string $field, string $key): void
    {
        $context = $this->context();
        $own = $this->records($context);
        $foreign = $this->records($this->context());
        foreach ([I::ViewEvaluations, I::HowToRecordEvaluations] as $intent) {
            $allowed = app(RuleEngine::class)->evaluate(new ChatbotFacts($context, $intent, ...[$field => $own[$key]]));
            $denied = app(RuleEngine::class)->evaluate(new ChatbotFacts($context, $intent, ...[$field => $foreign[$key]]));
            $this->assertSame(S::Allowed, $allowed->result);
            $this->assertSame(S::Denied, $denied->result);
        }
    }

    public function test_deleted_evaluation_and_invalid_level_are_denied(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        DB::table('evaluaciones')->where('id', $ids['evaluation'])->update(['unidad_id' => 2]);
        $facts = new ChatbotFacts($context, I::ViewEvaluations, evaluationId: $ids['evaluation']);
        $this->assertSame(S::Denied, app(RuleEngine::class)->evaluate($facts)->result);
        DB::table('evaluaciones')->where('id', $ids['evaluation'])->update(['unidad_id' => 1, 'deleted_at' => now()]);
        $this->assertSame(S::Denied, app(RuleEngine::class)->evaluate($facts)->result);
    }

    public function test_boleta_requires_selection_and_checks_resource_relationship(): void
    {
        $context = $this->context(R::Secretaria);
        $one = $this->records($this->context());
        $two = $this->records($this->context());
        $missing = app(RuleEngine::class)->evaluate(new ChatbotFacts($context, I::ViewBoleta));
        $this->assertSame(S::MissingContext, $missing->result);
        $this->assertSame(['required' => ['assignmentId', 'inscriptionId']], $missing->context);
        $valid = app(RuleEngine::class)->evaluate(new ChatbotFacts($context, I::ViewBoleta, assignmentId: $one['assignment'], inscriptionId: $one['inscription']));
        $this->assertSame(S::Allowed, $valid->result);
        $this->assertContains('RES-004', $valid->matchedRules);
        $mismatch = app(RuleEngine::class)->evaluate(new ChatbotFacts($context, I::ViewBoleta, assignmentId: $one['assignment'], inscriptionId: $two['inscription']));
        $this->assertSame(S::Denied, $mismatch->result);
    }

    public function test_outside_inscription_precedes_ambiguity_in_authorized_assignment(): void
    {
        $context = $this->context(R::CoordinadorComunidades);
        $own = $this->records($this->context());
        $foreign = $this->records($this->context(), community: 2);
        DB::table('asigna_grupo')->insert([
            'catequista_id' => $this->context()->userId, 'comunidad_id' => 1,
            'grupo_id' => $own['group'], 'nivel_id' => 2, 'periodo_id' => 1,
        ]);
        $result = app(RuleEngine::class)->evaluate(new ChatbotFacts($context, I::ViewBoleta, assignmentId: $own['assignment'], inscriptionId: $foreign['inscription']));
        $this->assertSame(S::Denied, $result->result);
        $this->assertSame('OUTSIDE_SCOPE', $result->code);
        $this->assertNotContains('AMB-001', $result->matchedRules);
    }

    public static function spoofedAuthority(): array
    {
        return [['role', 'secretaria'], ['userId', 999], ['status', 'aprobado'], ['communityId', 2]];
    }

    #[DataProvider('spoofedAuthority')]
    public function test_external_authority_is_ignored_by_resolver_and_not_accepted_by_facts(string $field, mixed $value): void
    {
        $context = $this->context();
        $request = Request::create('/', 'POST', [$field => $value, 'role' => 'secretaria', 'user_id' => 999, 'comunidad_id' => 2]);
        $request->setUserResolver(fn () => User::findOrFail($context->userId));
        $resolved = app(AccessContextResolver::class)->resolve($request);
        $this->assertSame($context->userId, $resolved->userId);
        $this->assertSame(R::Catequista, $resolved->role);
        $this->assertSame(1, $resolved->communityId);
        $result = app(RuleEngine::class)->evaluate(new ChatbotFacts($resolved, I::HowToManageUsers));
        $this->assertSame(S::Denied, $result->result);
        $this->expectException(\Error::class);
        new ChatbotFacts($resolved, I::HelpSystem, ...[$field => $value]);
    }

    public function test_how_to_intents_never_issue_writes_even_with_concrete_resources(): void
    {
        $teacher = $this->context();
        $secretary = $this->context(R::Secretaria);
        $ids = $this->records($teacher);
        $engine = app(RuleEngine::class);
        DB::enableQueryLog();
        DB::flushQueryLog();
        foreach (I::cases() as $intent) {
            if (str_starts_with($intent->value, 'HOW_TO_')) {
                $this->assertSame(S::Allowed, $engine->evaluate(new ChatbotFacts($secretary, $intent))->result);
            }
        }
        foreach (['assignmentId' => 'assignment', 'inscriptionId' => 'inscription', 'studentId' => 'student', 'evaluationId' => 'evaluation'] as $field => $key) {
            $this->assertSame(S::Allowed, $engine->evaluate(new ChatbotFacts($teacher, I::HowToRecordEvaluations, ...[$field => $ids[$key]]))->result);
        }
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertNotEmpty($queries);
        foreach ($queries as $query) {
            $this->assertMatchesRegularExpression('/^select\b/i', ltrim($query['query']));
        }
    }
}
