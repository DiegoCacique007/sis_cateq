<?php

namespace Tests\Feature\Chatbot;

use App\Enums\UserRole as R;
use App\Models\User;
use App\Services\Chatbot\ChatbotService;
use App\Services\Chatbot\Contracts\IntentResolver;
use App\Services\Chatbot\Intent\IntentResolution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CatequesisWebTestCase;

class ChatbotEndpointTest extends CatequesisWebTestCase
{
    private function login(R $role = R::Catequista): \App\Services\Authorization\AccessContext
    {
        $context = $this->context($role);
        $this->actingAs(User::findOrFail($context->userId));

        return $context;
    }

    public function test_guest_is_denied_with_json_contract(): void
    {
        $this->postJson(route('chatbot.message'), ['message' => 'ayuda'])->assertUnauthorized()->assertJsonPath('code', 'AUTH_REQUIRED');
    }

    public static function invalidAccounts(): array
    {
        return [['pendiente', 'catequista'], ['bloqueado', 'catequista'], ['aprobado', 'admin']];
    }

    #[DataProvider('invalidAccounts')]
    public function test_nonoperative_accounts_cannot_access(string $status, string $role): void
    {
        $user = User::factory()->create(['status' => $status, 'role' => $role]);
        $this->actingAs($user)->postJson(route('chatbot.message'), ['message' => 'ayuda'])
            ->assertForbidden()->assertJsonPath('code', 'ACCESS_DENIED');
    }

    public static function roles(): array
    {
        return array_map(fn ($role) => [$role], R::cases());
    }

    #[DataProvider('roles')]
    public function test_all_roles_have_help_and_shared_component(R $role): void
    {
        $this->login($role);
        $this->postJson(route('chatbot.message'), ['message' => '¿Qué puedes hacer?'])
            ->assertOk()->assertJsonPath('status', 'success')->assertJsonStructure(['status', 'code', 'message', 'data' => ['items', 'options'], 'actions', 'meta']);
        $html = view('components.chatbot')->render();
        $this->assertStringContainsString('data-chatbot', $html);
        $this->assertStringContainsString('chatbot/message', $html);
        $layout = $role === R::Secretaria ? 'admin' : $role->value;
        $this->assertStringContainsString('<x-chatbot />', file_get_contents(resource_path('views/layouts/app_parroquia_'.$layout.'.blade.php')));
    }

    public static function invalidPayloads(): array
    {
        return [
            [[]], [['message' => '']], [['message' => '   ']], [['message' => 'a']], [['message' => str_repeat('a', 2001)]],
            [['message' => ['ayuda']]], [['message' => 'ayuda', 'assignment_id' => -1]],
            [['message' => 'ayuda', 'assignment_id' => 0]], [['message' => 'ayuda', 'student_id' => 'abc']],
            [['message' => 'ayuda', 'inscription_id' => 1.2]], [['message' => 'ayuda', 'evaluation_id' => []]],
            [['message' => 'ayuda', 'role' => 'secretaria']], [['message' => 'ayuda', 'user_id' => 999]],
            [['message' => 'ayuda', 'communityId' => 2]], [['message' => 'ayuda', 'status' => 'aprobado']],
            [['message' => 'ayuda', 'intent' => 'HELP_SYSTEM']],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation_rejects_bad_payloads_and_authority_fields(array $payload): void
    {
        $this->login();
        $this->postJson(route('chatbot.message'), $payload)->assertUnprocessable()->assertJsonPath('code', 'INVALID_INPUT');
    }

    public function test_groups_selection_and_followup_are_real_and_scoped(): void
    {
        $context = $this->login();
        $own = $this->records($context);
        $second = $this->records($context);
        $other = $this->records($this->context());
        DB::table('grupos')->where('id', $own['group'])->update(['nombre' => 'Visible propio']);
        DB::table('grupos')->where('id', $other['group'])->update(['nombre' => 'Secreto ajeno']);
        DB::table('alumnos')->where('id', $own['student'])->update(['nombre' => 'Alumno autorizado']);
        $this->postJson(route('chatbot.message'), ['message' => 'Muéstrame mis grupos.'])
            ->assertOk()->assertSee('Visible propio')->assertDontSee('Secreto ajeno')->assertJsonMissingPath('data.groups.0.groupId');
        $selection = $this->postJson(route('chatbot.message'), ['message' => 'Muéstrame mis alumnos.'])
            ->assertOk()->assertJsonPath('status', 'selection_required')->assertJsonCount(2, 'data.options');
        $this->assertSame([$own['assignment'], $second['assignment']], array_column($selection->json('data.options'), 'id'));
        $this->postJson(route('chatbot.message'), ['message' => 'Muéstrame mis alumnos.', 'assignment_id' => $own['assignment']])
            ->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(1, 'data.items')->assertSee('Alumno autorizado');
        $this->postJson(route('chatbot.message'), ['message' => 'Muéstrame mis alumnos.', 'assignment_id' => $other['assignment']])
            ->assertOk()->assertJsonPath('status', 'denied')->assertJsonCount(0, 'data.items');
    }

    public function test_community_selection_excludes_foreign_assignment(): void
    {
        $context = $this->login(R::CoordinadorComunidades);
        $own = $this->records($context);
        $foreign = $this->records($context, community: 2);
        $this->postJson(route('chatbot.message'), ['message' => 'muéstrame evaluaciones'])
            ->assertOk()->assertJsonCount(1, 'data.options')->assertJsonPath('data.options.0.id', $own['assignment']);
        $this->postJson(route('chatbot.message'), ['message' => 'muéstrame evaluaciones', 'assignment_id' => $foreign['assignment']])
            ->assertOk()->assertJsonPath('status', 'denied');
    }

    public function test_unknown_ambiguity_and_denial_are_distinct(): void
    {
        $this->login();
        $this->postJson(route('chatbot.message'), ['message' => 'clima mañana'])->assertJsonPath('status', 'unsupported');
        $this->postJson(route('chatbot.message'), ['message' => 'muéstrame alumnos y calificaciones'])
            ->assertJsonPath('status', 'ambiguous')->assertJsonPath('code', 'AMBIGUOUS_INTENT')->assertJsonCount(0, 'data.options');
        $denied = $this->postJson(route('chatbot.message'), ['message' => 'Modifica este alumno.'])->assertJsonPath('status', 'denied');
        $this->assertStringContainsString('Secretaría', $denied->json('message'));
    }

    public function test_help_without_period_works_while_academic_queries_require_it(): void
    {
        $this->login();
        $this->withSession(['periodo_activo_id' => null]);
        $this->postJson(route('chatbot.message'), ['message' => 'ayuda'])->assertOk()->assertJsonPath('status', 'success');
        $this->postJson(route('chatbot.message'), ['message' => 'mis grupos'])->assertOk()->assertJsonPath('status', 'missing_context')->assertJsonPath('code', 'MISSING_PERIOD');
    }

    public function test_context_is_reloaded_when_account_is_revoked(): void
    {
        $context = $this->login();
        $this->postJson(route('chatbot.message'), ['message' => 'ayuda'])->assertOk();
        DB::table('users')->where('id', $context->userId)->update(['status' => 'bloqueado']);
        $this->postJson(route('chatbot.message'), ['message' => 'ayuda'])->assertForbidden()->assertJsonPath('status', 'denied');
    }

    public function test_limit_is_thirty_per_user_and_does_not_block_another_user(): void
    {
        $this->login();
        for ($i = 0; $i < 30; $i++) {
            $this->postJson(route('chatbot.message'), ['message' => 'ayuda'])->assertOk();
        }
        $this->postJson(route('chatbot.message'), ['message' => 'ayuda'])->assertStatus(429)->assertJsonPath('code', 'RATE_LIMITED');
        $this->login();
        $this->postJson(route('chatbot.message'), ['message' => 'ayuda'])->assertOk();
    }

    public function test_csrf_is_required_outside_testing_bypass(): void
    {
        $this->login();
        $this->app->instance('env', 'local');
        $this->postJson(route('chatbot.message'), ['message' => 'ayuda'])->assertStatus(419)->assertJsonPath('code', 'SESSION_EXPIRED');
    }

    public function test_unexpected_error_is_sanitized_even_with_debug_enabled(): void
    {
        $this->login();
        config(['app.debug' => true]);
        $this->app->instance(IntentResolver::class, new class implements IntentResolver
        {
            public function resolve(string $message): IntentResolution
            {
                throw new \RuntimeException('SQL password secret internal path');
            }
        });
        Log::spy();
        $response = $this->postJson(route('chatbot.message'), ['message' => 'ayuda']);
        $response->assertStatus(500)->assertJsonPath('code', 'CHATBOT_ERROR')->assertDontSee('SQL')->assertDontSee('password')->assertDontSee('trace');
        Log::shouldHaveReceived('error')->once()->with('chatbot.failure', ['code' => 'CHATBOT_ERROR', 'exception_class' => \RuntimeException::class]);
    }

    public function test_service_chain_and_guidance_for_secretary(): void
    {
        $context = $this->login(R::Secretaria);
        $request = Request::create('/chatbot/message', 'POST');
        $request->setUserResolver(fn () => User::findOrFail($context->userId));
        $request->setLaravelSession($this->app['session.store']);
        $response = app(ChatbotService::class)->handle($request, '¿Cómo registro un alumno?');
        $this->assertSame('GUIDANCE_READY', $response->code);
        $this->assertStringContainsString('Nuevo alumno', implode(' ', $response->data['steps']));
        $this->postJson(route('chatbot.message'), ['message' => '¿Cómo hago una inscripción?'])->assertJsonPath('status', 'success')->assertJsonPath('actions.0.type', 'navigate');
    }

    public function test_unknowing_help_request_reaches_guidance_and_real_negation_does_not(): void
    {
        $this->login(R::Secretaria);
        $this->postJson(route('chatbot.message'), ['message' => 'No sé cómo registrar un alumno'])
            ->assertOk()->assertJsonPath('status', 'success')->assertJsonPath('code', 'GUIDANCE_READY')
            ->assertJsonPath('actions.0.type', 'navigate');
        $this->postJson(route('chatbot.message'), ['message' => '¿Cómo doy de alta a un alumno?'])
            ->assertOk()->assertJsonPath('code', 'GUIDANCE_READY');
        $this->postJson(route('chatbot.message'), ['message' => 'No quiero consultar alumnos'])
            ->assertOk()->assertJsonPath('status', 'unsupported')->assertJsonPath('code', 'UNKNOWN_INTENT')
            ->assertJsonPath('data.items', []);
    }

    public function test_catechist_reporting_missing_students_gets_own_assignments(): void
    {
        $context = $this->login();
        $own = $this->records($context);
        $this->records($this->context());
        $this->postJson(route('chatbot.message'), ['message' => 'No puedo ver mis alumnos'])
            ->assertOk()->assertJsonPath('status', 'selection_required')->assertJsonCount(1, 'data.options')
            ->assertJsonPath('data.options.0.id', $own['assignment']);
    }

    public function test_catechist_without_assignments_in_period_is_told_to_request_review(): void
    {
        $context = $this->login();
        $this->records($context, period: 2);
        $response = $this->postJson(route('chatbot.message'), ['message' => 'No puedo ver mis alumnos'])
            ->assertOk()->assertJsonPath('status', 'selection_required')->assertJsonCount(0, 'data.options');
        $this->assertStringContainsString('Secretaría', $response->json('message'));
    }

    public function test_secretary_reporting_blocked_registration_gets_guidance(): void
    {
        $this->login(R::Secretaria);
        $this->postJson(route('chatbot.message'), ['message' => 'No me deja registrar un alumno'])
            ->assertOk()->assertJsonPath('status', 'success')->assertJsonPath('code', 'GUIDANCE_READY')
            ->assertJsonPath('actions.0.type', 'navigate');
    }

    public static function supervisorRoles(): array
    {
        return [[R::Catequista], [R::Parroco], [R::CoordinadorGeneral], [R::CoordinadorComunidades]];
    }

    #[DataProvider('supervisorRoles')]
    public function test_non_secretary_reporting_blocked_registration_is_sent_to_secretary(R $role): void
    {
        $this->login($role);
        $response = $this->postJson(route('chatbot.message'), ['message' => 'No me deja registrar un alumno'])
            ->assertOk()->assertJsonPath('status', 'denied')->assertJsonPath('code', 'ROLE_NOT_ALLOWED');
        $this->assertStringContainsString('Acude a Secretaría.', $response->json('message'));
    }

    public function test_general_coordinator_reporting_missing_grades_gets_selection(): void
    {
        $this->login(R::CoordinadorGeneral);
        $ids = $this->records($this->context());
        $this->postJson(route('chatbot.message'), ['message' => 'No me aparecen las calificaciones'])
            ->assertOk()->assertJsonPath('status', 'selection_required')->assertJsonPath('data.options.0.id', $ids['assignment']);
        $this->postJson(route('chatbot.message'), ['message' => 'No me aparecen las calificaciones', 'assignment_id' => $ids['assignment']])
            ->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(1, 'data.items');
    }

    public function test_community_coordinator_reporting_missing_group_sees_only_own_community(): void
    {
        $context = $this->login(R::CoordinadorComunidades);
        $own = $this->records($context);
        $foreign = $this->records($context, community: 2);
        DB::table('grupos')->where('id', $own['group'])->update(['nombre' => 'Grupo propio']);
        DB::table('grupos')->where('id', $foreign['group'])->update(['nombre' => 'Grupo ajeno']);
        $this->postJson(route('chatbot.message'), ['message' => 'No encuentro mi grupo'])
            ->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(1, 'data.items')
            ->assertSee('Grupo propio')->assertDontSee('Grupo ajeno');
    }

    /** Efectos aceptados de reconocer «no puedo / no me deja»: [frase, rol, status, code, menciona Secretaría]. */
    public static function acceptedSideEffects(): array
    {
        $attendance = 'No puedo descargar la lista de asistencia';
        $boleta = 'No puedo descargar la boleta';
        $edit = 'No me deja editar un alumno';
        $cases = [
            [$attendance, R::Catequista, 'selection_required', 'ASSIGNMENT_SELECTION_REQUIRED', false],
            [$boleta, R::Catequista, 'denied', 'ROLE_NOT_ALLOWED', false],
            [$edit, R::Catequista, 'denied', 'ROLE_NOT_ALLOWED', true],
            [$attendance, R::Secretaria, 'denied', 'ROLE_NOT_ALLOWED', false],
            [$boleta, R::Secretaria, 'missing_context', 'MISSING_RESOURCE', false],
            [$edit, R::Secretaria, 'unsupported', 'EXECUTION_UNSUPPORTED', false],
        ];
        foreach ([R::Parroco, R::CoordinadorGeneral, R::CoordinadorComunidades] as $role) {
            $cases[] = [$attendance, $role, 'denied', 'ROLE_NOT_ALLOWED', false];
            $cases[] = [$boleta, $role, 'missing_context', 'MISSING_RESOURCE', false];
            $cases[] = [$edit, $role, 'denied', 'ROLE_NOT_ALLOWED', true];
        }

        return $cases;
    }

    #[DataProvider('acceptedSideEffects')]
    public function test_problem_report_side_effects_are_intentional(string $message, R $role, string $status, string $code, bool $secretary): void
    {
        $context = $this->login($role);
        $this->records($context);
        $response = $this->postJson(route('chatbot.message'), ['message' => $message])
            ->assertOk()->assertJsonPath('status', $status)->assertJsonPath('code', $code)->assertJsonPath('data.items', []);
        $this->assertSame($secretary, str_contains($response->json('message'), 'Acude a Secretaría.'));
    }

    /** [rol, ruta del módulo de evaluaciones del propio rol]. */
    public static function evaluationRecorders(): array
    {
        return [[R::Catequista, '/catequista/evaluaciones'], [R::Secretaria, '/secretaria/evaluaciones']];
    }

    #[DataProvider('evaluationRecorders')]
    public function test_grade_recording_problem_gets_guidance_for_own_module(R $role, string $url): void
    {
        $this->login($role);
        foreach (['No me deja registrar calificaciones', 'No puedo capturar las evaluaciones', 'No logro guardar las calificaciones de mi grupo'] as $message) {
            $this->postJson(route('chatbot.message'), ['message' => $message])
                ->assertOk()->assertJsonPath('status', 'success')->assertJsonPath('code', 'GUIDANCE_READY')
                ->assertJsonCount(3, 'data.items')->assertJsonPath('actions.0.type', 'navigate')
                ->assertJsonPath('actions.0.url', $url);
        }
    }

    public static function nonRecorderRoles(): array
    {
        return [[R::Parroco], [R::CoordinadorGeneral], [R::CoordinadorComunidades]];
    }

    #[DataProvider('nonRecorderRoles')]
    public function test_grade_recording_problem_is_denied_without_secretary_referral(R $role): void
    {
        $this->login($role);
        $response = $this->postJson(route('chatbot.message'), ['message' => 'No me deja registrar calificaciones'])
            ->assertOk()->assertJsonPath('status', 'denied')->assertJsonPath('code', 'ROLE_NOT_ALLOWED')
            ->assertJsonPath('data.items', [])->assertJsonPath('actions', []);
        $this->assertStringNotContainsString('Acude a Secretaría.', $response->json('message'));
    }

    public function test_grade_recording_problem_without_period_requires_it(): void
    {
        $this->login(R::Secretaria);
        $this->withSession(['periodo_activo_id' => null]);
        $this->postJson(route('chatbot.message'), ['message' => 'No puedo subir las calificaciones'])
            ->assertOk()->assertJsonPath('status', 'missing_context')->assertJsonPath('code', 'MISSING_PERIOD');
    }

    public function test_grade_write_command_stays_unsupported_without_data(): void
    {
        $this->login();
        $this->postJson(route('chatbot.message'), ['message' => 'guarda las calificaciones porque no me deja registrar'])
            ->assertOk()->assertJsonPath('status', 'unsupported')->assertJsonPath('code', 'UNKNOWN_INTENT')
            ->assertJsonPath('data.items', [])->assertJsonPath('actions', []);
    }

    #[DataProvider('evaluationRecorders')]
    public function test_possibility_and_place_questions_get_grade_guidance_for_own_module(R $role, string $url): void
    {
        $this->login($role);
        foreach (['¿Puedo registrar calificaciones?', '¿Dónde registro las calificaciones?', '¿Se puede registrar calificaciones?'] as $message) {
            $this->postJson(route('chatbot.message'), ['message' => $message])
                ->assertOk()->assertJsonPath('status', 'success')->assertJsonPath('code', 'GUIDANCE_READY')
                ->assertJsonCount(3, 'data.items')->assertJsonPath('actions.0.url', $url);
        }
    }

    #[DataProvider('nonRecorderRoles')]
    public function test_possibility_question_on_grades_is_answered_with_denial(R $role): void
    {
        $this->login($role);
        foreach (['¿Puedo registrar calificaciones?', '¿Dónde registro las calificaciones?'] as $message) {
            $response = $this->postJson(route('chatbot.message'), ['message' => $message])
                ->assertOk()->assertJsonPath('status', 'denied')->assertJsonPath('code', 'ROLE_NOT_ALLOWED')
                ->assertJsonPath('data.items', [])->assertJsonPath('actions', []);
            $this->assertStringNotContainsString('Acude a Secretaría.', $response->json('message'));
        }
    }

    public function test_grade_possibility_question_without_period_requires_it(): void
    {
        $this->login();
        $this->withSession(['periodo_activo_id' => null]);
        $this->postJson(route('chatbot.message'), ['message' => '¿Puedo registrar calificaciones?'])
            ->assertOk()->assertJsonPath('status', 'missing_context')->assertJsonPath('code', 'MISSING_PERIOD');
    }

    public function test_secretary_not_knowing_where_to_register_student_gets_guidance(): void
    {
        $this->login(R::Secretaria);
        $this->postJson(route('chatbot.message'), ['message' => 'No sé dónde dar de alta a un alumno'])
            ->assertOk()->assertJsonPath('status', 'success')->assertJsonPath('code', 'GUIDANCE_READY')
            ->assertJsonPath('actions.0.url', '/secretaria/alumnos');
    }

    #[DataProvider('supervisorRoles')]
    public function test_not_knowing_where_to_register_student_is_sent_to_secretary(R $role): void
    {
        $this->login($role);
        $response = $this->postJson(route('chatbot.message'), ['message' => 'No sé dónde dar de alta a un alumno'])
            ->assertOk()->assertJsonPath('status', 'denied')->assertJsonPath('code', 'ROLE_NOT_ALLOWED');
        $this->assertStringContainsString('Acude a Secretaría.', $response->json('message'));
    }

    public static function botDirectedGradeWrites(): array
    {
        return [['puedes registrar las calificaciones de mi grupo'], ['¿me puedes registrar calificaciones?'],
            ['¿podrías registrar las calificaciones?'], ['¿dónde registro la asistencia?']];
    }

    #[DataProvider('botDirectedGradeWrites')]
    public function test_asking_bot_to_write_stays_unsupported_without_data(string $message): void
    {
        $this->login(R::Secretaria);
        $this->postJson(route('chatbot.message'), ['message' => $message])
            ->assertOk()->assertJsonPath('status', 'unsupported')->assertJsonPath('code', 'UNKNOWN_INTENT')
            ->assertJsonPath('data.items', [])->assertJsonPath('actions', []);
    }

    public function test_parish_priest_can_read_but_cannot_register_student(): void
    {
        $context = $this->login(R::Parroco);
        $ids = $this->records($context);
        $this->postJson(route('chatbot.message'), ['message' => 'Muéstrame evaluaciones.', 'assignment_id' => $ids['assignment']])
            ->assertJsonPath('status', 'success')->assertJsonCount(1, 'data.items');
        $this->postJson(route('chatbot.message'), ['message' => '¿Cómo registro un alumno?'])->assertJsonPath('status', 'denied');
    }
}
