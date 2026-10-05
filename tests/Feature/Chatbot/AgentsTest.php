<?php

namespace Tests\Feature\Chatbot;

use App\Enums\ChatbotIntent as I;
use App\Enums\UserRole as R;
use App\Services\Authorization\AccessContext;
use App\Services\Chatbot\Agents\AdministrationAgent;
use App\Services\Chatbot\Agents\AgentRequest;
use App\Services\Chatbot\Agents\AgentResponse;
use App\Services\Chatbot\Agents\EvaluationsAgent;
use App\Services\Chatbot\Agents\GroupsAgent;
use App\Services\Chatbot\Agents\StudentsAgent;
use App\Services\Chatbot\Intent\RuleBasedIntentResolver;
use App\Services\Chatbot\Knowledge\SystemGuidance;
use App\Services\Chatbot\Routing\AgentRegistry;
use App\Services\Chatbot\Rules\ChatbotFacts;
use App\Services\Chatbot\Rules\RuleEngine;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CatequesisWebTestCase;

class AgentsTest extends CatequesisWebTestCase
{
    private function requestFor(AccessContext $context, I $intent, array $ids = []): AgentRequest
    {
        return AgentRequest::evaluate(new ChatbotFacts($context, $intent, ...$ids), app(RuleEngine::class));
    }

    private function response(AccessContext $context, I $intent, array $ids = []): AgentResponse
    {
        $request = $this->requestFor($context, $intent, $ids);

        return app(AgentRegistry::class)->resolve($request)->handle($request);
    }

    public function test_groups_only_include_own_live_assignments_in_active_period(): void
    {
        $context = $this->context();
        $own = $this->records($context);
        $this->records($this->context());
        $this->records($context, period: 2);
        $deleted = $this->records($context);
        DB::table('asigna_grupo')->where('id', $deleted['assignment'])->update(['deleted_at' => now()]);
        $response = $this->response($context, I::ViewMyGroups);
        $this->assertSame('OK', $response->status);
        $this->assertSame([$own['assignment']], array_column($response->data['groups'], 'assignmentId'));
        $this->assertSame(['assignmentId', 'groupId', 'name', 'level', 'community', 'period'], array_keys($response->data['groups'][0]));
    }

    public static function supervisors(): array
    {
        return [[R::Parroco, 2], [R::CoordinadorGeneral, 2], [R::CoordinadorComunidades, 1]];
    }

    #[DataProvider('supervisors')]
    public function test_supervisor_groups_respect_community_scope(R $role, int $count): void
    {
        $teacher = $this->context();
        $this->records($teacher);
        $this->records($teacher, community: 2);
        $this->records($teacher, period: 2);
        $response = $this->response($this->context($role), I::ViewMyGroups);
        $this->assertCount($count, $response->data['groups']);
    }

    public function test_assignment_id_filter_reduces_group_results(): void
    {
        $context = $this->context();
        $one = $this->records($context);
        $this->records($context);
        $result = $this->response($context, I::ViewMyGroups, ['assignmentId' => $one['assignment']]);
        $this->assertSame([$one['assignment']], array_column($result->data['groups'], 'assignmentId'));
    }

    public function test_students_are_limited_to_selected_assignment(): void
    {
        $context = $this->context();
        $one = $this->records($context);
        $this->records($context);
        $this->records($this->context());
        $response = $this->response($context, I::ViewGroupStudents, ['assignmentId' => $one['assignment']]);
        $this->assertSame('OK', $response->status);
        $this->assertSame([$one['student']], array_column($response->data['students'], 'id'));
        $this->assertSame(['id', 'fullName'], array_keys($response->data['students'][0]));
    }

    public function test_missing_assignment_returns_only_accessible_options_without_autoselect(): void
    {
        $context = $this->context();
        $one = $this->records($context);
        $two = $this->records($context);
        $this->records($this->context());
        $this->records($context, period: 2);
        $response = $this->response($context, I::ViewGroupStudents);
        $this->assertSame('RESOURCE_SELECTION_REQUIRED', $response->status);
        $this->assertSame('assignment', $response->data['resourceType']);
        $this->assertSame([$one['assignment'], $two['assignment']], array_column($response->data['options'], 'assignmentId'));
        $this->assertArrayNotHasKey('students', $response->data);
    }

    public function test_even_one_or_zero_assignments_require_explicit_selection(): void
    {
        $context = $this->context();
        $empty = $this->response($context, I::ViewGroupStudents);
        $this->assertSame('RESOURCE_SELECTION_REQUIRED', $empty->status);
        $this->assertSame([], $empty->data['options']);
        $this->records($context);
        $single = $this->response($context, I::ViewGroupStudents);
        $this->assertSame('RESOURCE_SELECTION_REQUIRED', $single->status);
        $this->assertCount(1, $single->data['options']);
    }

    public function test_foreign_and_nonexistent_ids_produce_identical_safe_responses(): void
    {
        $context = $this->context();
        $foreign = $this->records($this->context());
        $one = $this->response($context, I::ViewGroupStudents, ['assignmentId' => $foreign['assignment']]);
        $two = $this->response($context, I::ViewGroupStudents, ['assignmentId' => 99999]);
        $this->assertEquals($one, $two);
        $this->assertSame('DENIED', $one->status);
        $this->assertSame([], $one->data);
    }

    public function test_direct_agent_call_rechecks_scope_after_assignment_changes(): void
    {
        $context = $this->context();
        $own = $this->records($context);
        $request = $this->requestFor($context, I::ViewGroupStudents, ['assignmentId' => $own['assignment']]);
        DB::table('asigna_grupo')->where('id', $own['assignment'])->update(['catequista_id' => $this->context()->userId]);
        $response = app(StudentsAgent::class)->handle($request);
        $this->assertSame('DENIED', $response->status);
        $this->assertSame([], $response->data);
    }

    public function test_ambiguous_data_is_not_conversational_selection(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        DB::table('asigna_grupo')->insert(['catequista_id' => $context->userId, 'grupo_id' => $ids['group'], 'nivel_id' => 2, 'periodo_id' => 1, 'comunidad_id' => 1]);
        $response = $this->response($context, I::ViewGroupStudents, ['assignmentId' => $ids['assignment']]);
        $this->assertSame('AMBIGUOUS', $response->status);
        $this->assertSame('AMBIGUOUS_ASSIGNMENT', $response->code);
        $this->assertSame([], $response->data);
        $selection = $this->response($context, I::ViewGroupStudents);
        $this->assertSame([], $selection->data['options']);
    }

    public function test_attendance_only_offers_existing_navigation_for_authorized_assignment(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        $this->assertSame('RESOURCE_SELECTION_REQUIRED', $this->response($context, I::ViewAttendanceList)->status);
        $response = $this->response($context, I::ViewAttendanceList, ['assignmentId' => $ids['assignment']]);
        $this->assertSame('OK', $response->status);
        $this->assertSame('catequista.mi_grupo', $response->suggestedActions[0]['route']);
        $this->assertTrue(Route::has($response->suggestedActions[0]['route']));
    }

    public static function evaluationFilters(): array
    {
        return [['assignmentId', 'assignment'], ['inscriptionId', 'inscription'], ['studentId', 'student'], ['evaluationId', 'evaluation']];
    }

    #[DataProvider('evaluationFilters')]
    public function test_evaluation_filters_never_expand_access(string $field, string $key): void
    {
        $context = $this->context();
        $own = $this->records($context);
        $otherOwn = $this->records($context);
        $foreign = $this->records($this->context());
        $response = $this->response($context, I::ViewEvaluations, [$field => $own[$key]]);
        $this->assertSame('OK', $response->status);
        $this->assertCount(1, $response->data['evaluations']);
        $this->assertSame($own['student'], $response->data['evaluations'][0]['student']['id']);
        $this->assertNotSame($otherOwn['student'], $response->data['evaluations'][0]['student']['id']);
        $denied = $this->response($context, I::ViewEvaluations, [$field => $foreign[$key]]);
        $this->assertSame('DENIED', $denied->status);
        $this->assertSame([], $denied->data);
    }

    public function test_evaluations_without_filter_request_selection(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        $response = $this->response($context, I::ViewEvaluations);
        $this->assertSame('RESOURCE_SELECTION_REQUIRED', $response->status);
        $this->assertSame([$ids['assignment']], array_column($response->data['options'], 'assignmentId'));
    }

    public function test_historical_null_period_inherits_inscription_but_foreign_period_is_excluded(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        DB::table('evaluaciones')->where('id', $ids['evaluation'])->update(['periodo_id' => null]);
        $response = $this->response($context, I::ViewEvaluations, ['assignmentId' => $ids['assignment']]);
        $this->assertCount(1, $response->data['evaluations']);
        DB::table('evaluaciones')->where('id', $ids['evaluation'])->update(['periodo_id' => 2]);
        $this->assertSame([], $this->response($context, I::ViewEvaluations, ['assignmentId' => $ids['assignment']])->data['evaluations']);
    }

    public static function deletedDependencies(): array
    {
        return [['alumnos', 'student'], ['inscripciones', 'inscription'], ['evaluaciones', 'evaluation']];
    }

    #[DataProvider('deletedDependencies')]
    public function test_deleted_evaluation_dependencies_are_excluded(string $table, string $key): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        DB::table($table)->where('id', $ids[$key])->update(['deleted_at' => now()]);
        $response = $this->response($context, I::ViewEvaluations, ['assignmentId' => $ids['assignment']]);
        $this->assertSame([], $response->data['evaluations']);
    }

    public static function guidanceIntents(): array
    {
        return array_map(fn (I $intent) => [$intent], AdministrationAgent::intents());
    }

    #[DataProvider('guidanceIntents')]
    public function test_secretary_guidance_has_real_get_route_without_database_queries(I $intent): void
    {
        $context = $this->context(R::Secretaria);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $response = $this->response($context, $intent);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertSame('OK', $response->status);
        $this->assertNotEmpty($response->data['steps']);
        $this->assertSame(false, $response->metadata['execution']);
        $route = Route::getRoutes()->getByName($response->suggestedActions[0]['route']);
        $this->assertNotNull($route);
        $this->assertContains('GET', $route->methods());
        $this->assertSame([], $queries);
    }

    public function test_guidance_buttons_match_existing_forms(): void
    {
        $guidance = app(SystemGuidance::class);
        foreach ([I::HowToRegisterStudent->value => ['alumnos', 'Nuevo alumno'], I::HowToRegisterInscription->value => ['inscripciones', 'Nueva inscripción']] as $intent => [$module, $button]) {
            $this->assertStringContainsString($button, file_get_contents(resource_path('views/secretaria/'.$module.'/index.blade.php')));
            $entry = $guidance->for(I::from($intent), R::Secretaria);
            $this->assertStringContainsString($button, implode(' ', $entry['steps']));
        }
    }

    public function test_catechist_cannot_get_administrative_guidance_even_by_direct_call(): void
    {
        $request = $this->requestFor($this->context(), I::HowToRegisterStudent);
        $response = app(AdministrationAgent::class)->handle($request);
        $this->assertSame('DENIED', $response->status);
        $this->assertSame([], $response->data);
        $this->assertSame('secretaria', $response->suggestedActions[0]['responsible']);
    }

    public function test_catechist_evaluation_guidance_matches_own_form(): void
    {
        $response = $this->response($this->context(), I::HowToRecordEvaluations);
        $this->assertSame('catequista.evaluaciones.index', $response->suggestedActions[0]['route']);
        $this->assertStringContainsString('total posible', implode(' ', $response->data['steps']));
    }

    public function test_help_lists_only_capabilities_of_context_without_queries(): void
    {
        $context = $this->context();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $response = $this->response($context, I::HelpSystem);
        $this->assertSame([], DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertContains(I::ViewAttendanceList->value, $response->data['availableIntents']);
        $this->assertNotContains(I::HowToRegisterStudent->value, $response->data['availableIntents']);
        $this->assertNotContains(I::ViewBoleta->value, $response->data['availableIntents']);
    }

    public function test_escalation_preserves_denial_unsupported_and_missing_context(): void
    {
        $denied = $this->response($this->context(), I::ModifyStudent, ['studentId' => 99999]);
        $this->assertSame('DENIED', $denied->status);
        $this->assertSame('secretaria', $denied->suggestedActions[0]['responsible']);
        $this->assertSame([], $denied->data);
        $unknown = $this->response($this->context(), I::Unknown);
        $this->assertSame('UNSUPPORTED', $unknown->status);
        $this->assertSame('chatbot.function_unavailable', $unknown->messageKey);
        $missing = $this->response($this->context(period: null), I::ViewGroupStudents);
        $this->assertSame('MISSING_CONTEXT', $missing->status);
        $this->assertSame(['required' => ['activePeriodId']], $missing->data);
        $community = $this->response($this->context(R::CoordinadorComunidades, community: null), I::ViewEvaluations);
        $this->assertSame(['required' => ['communityId']], $community->data);
    }

    public function test_safe_projections_contain_no_private_attributes_or_models(): void
    {
        Schema::table('alumnos', fn (Blueprint $t) => $t->string('datos_sacramentales')->nullable());
        $context = $this->context();
        $ids = $this->records($context);
        DB::table('alumnos')->where('id', $ids['student'])->update(['datos_sacramentales' => 'PRIVATE_SACRAMENT', 'fecha_nacimiento' => '2015-01-01']);
        DB::table('tutores')->insert(['alumno_id' => $ids['student'], 'nombre' => 'Tutor', 'telefono' => 'PRIVATE_PHONE']);
        foreach ([I::ViewMyGroups, I::ViewGroupStudents, I::ViewEvaluations] as $intent) {
            $response = $this->response($context, $intent, ['assignmentId' => $ids['assignment']]);
            $json = json_encode($response, JSON_THROW_ON_ERROR);
            foreach (['password', 'remember_token', 'PRIVATE_', 'fecha_nacimiento', 'datos_sacramentales', 'telefono', 'catequista_id'] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $json);
            }
            $projected = $response->data;
            array_walk_recursive($projected, fn ($value) => $this->assertFalse(is_object($value)));
        }
        $evaluation = $this->response($context, I::ViewEvaluations, ['assignmentId' => $ids['assignment']])->data['evaluations'][0];
        $this->assertSame(['student', 'unit', 'rubric', 'grade'], array_keys($evaluation));
    }

    public function test_wrong_agent_cannot_return_data_for_other_intent(): void
    {
        $request = $this->requestFor($this->context(), I::HelpSystem);
        foreach ([GroupsAgent::class, StudentsAgent::class, EvaluationsAgent::class] as $agent) {
            $response = app($agent)->handle($request);
            $this->assertSame('UNSUPPORTED', $response->status);
            $this->assertSame([], $response->data);
        }
    }

    public function test_full_message_to_agent_chain(): void
    {
        $teacher = $this->context();
        $this->records($teacher);
        $this->records($teacher);
        $secretary = $this->context(R::Secretaria);
        foreach ([
            ['muéstrame mis grupos', $teacher, 'OK', 'groups'],
            ['muéstrame mis alumnos', $teacher, 'RESOURCE_SELECTION_REQUIRED', 'options'],
            ['cómo registro un alumno', $secretary, 'OK', 'steps'],
            ['modifica este alumno', $teacher, 'DENIED', null],
        ] as [$message, $context, $status, $field]) {
            $resolution = (new RuleBasedIntentResolver)->resolve($message);
            $this->assertFalse($resolution->ambiguous);
            $response = $this->response($context, $resolution->intent);
            $this->assertSame($status, $response->status);
            if ($field) {
                $this->assertNotEmpty($response->data[$field]);
            } else {
                $this->assertSame('secretaria', $response->suggestedActions[0]['responsible']);
            }
        }
    }

    public function test_community_students_and_evaluations_exclude_other_community(): void
    {
        $teacher = $this->context();
        $own = $this->records($teacher);
        $foreign = $this->records($teacher, community: 2);
        $context = $this->context(R::CoordinadorComunidades);
        foreach ([I::ViewGroupStudents, I::ViewEvaluations] as $intent) {
            $this->assertSame('OK', $this->response($context, $intent, ['assignmentId' => $own['assignment']])->status);
            $this->assertSame('DENIED', $this->response($context, $intent, ['assignmentId' => $foreign['assignment']])->status);
            $options = $this->response($context, $intent)->data['options'];
            $this->assertSame([$own['assignment']], array_column($options, 'assignmentId'));
        }
    }

    public function test_no_data_is_queried_for_blocked_context(): void
    {
        $context = new AccessContext(1, R::Catequista, 'bloqueado', 1, 1);
        DB::enableQueryLog();
        DB::flushQueryLog();
        foreach ([I::ViewMyGroups, I::ViewGroupStudents, I::ViewEvaluations, I::HelpSystem] as $intent) {
            $response = $this->response($context, $intent);
            $this->assertSame('DENIED', $response->status);
            $this->assertSame([], $response->data);
        }
        $this->assertSame([], DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_read_and_concrete_guidance_agents_only_issue_select_queries(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        DB::enableQueryLog();
        DB::flushQueryLog();
        foreach ([I::ViewMyGroups, I::ViewGroupStudents, I::ViewEvaluations, I::ViewAttendanceList, I::HowToRecordEvaluations] as $intent) {
            $this->assertSame('OK', $this->response($context, $intent, ['assignmentId' => $ids['assignment']])->status);
        }
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertNotEmpty($queries);
        foreach ($queries as $query) {
            $this->assertMatchesRegularExpression('/^select\b/i', ltrim($query['query']));
        }
    }

    public function test_lists_have_explicit_bound_and_truncation_metadata(): void
    {
        $context = $this->context();
        for ($i = 0; $i < 101; $i++) {
            $this->records($context);
        }
        $response = $this->response($context, I::ViewMyGroups);
        $this->assertCount(100, $response->data['groups']);
        $this->assertTrue($response->metadata['hasMore']);
    }

    public function test_boleta_is_only_navigation_after_resource_authorization(): void
    {
        $ids = $this->records($this->context());
        $context = $this->context(R::Parroco);
        $response = $this->response($context, I::ViewBoleta, ['assignmentId' => $ids['assignment'], 'inscriptionId' => $ids['inscription']]);
        $this->assertSame('BOLETA_MODULE_GUIDANCE', $response->code);
        $this->assertSame('parroco.boletas.index', $response->suggestedActions[0]['route']);
        $this->assertTrue(Route::has($response->suggestedActions[0]['route']));
        $this->assertArrayNotHasKey('evaluations', $response->data);
    }
}
