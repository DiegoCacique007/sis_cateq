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
    protected function setUp(): void
    {
        parent::setUp();
        // Cada respuesta registra un evento: la bitácora existe, como el resto del esquema en memoria.
        (require database_path('migrations/2026_10_18_000001_create_chatbot_events_table.php'))->up();
    }

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
        $this->postJson(route('chatbot.message'), ['message' => 'No puedo ver mis alumnos'])
            ->assertOk()->assertJsonPath('status', 'selection_required')->assertJsonCount(0, 'data.options')
            ->assertJsonPath('message', 'No tienes asignaciones en el periodo actual. Comprueba que sea el periodo correcto o solicita tu asignación a Secretaría.');
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
            ->assertOk()->assertJsonPath('status', 'unsupported')->assertJsonPath('code', 'GRADE_WRITE_UNSUPPORTED')
            ->assertJsonPath('message', 'El asistente no registra calificaciones. Puedes hacerlo en Evaluaciones.')
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('actions', [['type' => 'navigate', 'label' => 'Abrir módulo', 'url' => '/catequista/evaluaciones']]);
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

    /** [frase, código, enlace esperado para Secretaría o null]. */
    public static function botDirectedGradeWrites(): array
    {
        $grade = ['GRADE_WRITE_UNSUPPORTED', '/secretaria/evaluaciones'];

        return [['puedes registrar las calificaciones de mi grupo', ...$grade], ['¿me puedes registrar calificaciones?', ...$grade],
            ['¿podrías registrar las calificaciones?', ...$grade], ['¿dónde registro la asistencia?', 'ATTENDANCE_WRITE_UNSUPPORTED', null]];
    }

    #[DataProvider('botDirectedGradeWrites')]
    public function test_asking_bot_to_write_stays_unsupported_without_data(string $message, string $code, ?string $url): void
    {
        $this->login(R::Secretaria);
        $this->postJson(route('chatbot.message'), ['message' => $message])
            ->assertOk()->assertJsonPath('status', 'unsupported')->assertJsonPath('code', $code)
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('actions', $url ? [['type' => 'navigate', 'label' => 'Abrir módulo', 'url' => $url]] : []);
    }

    #[DataProvider('evaluationRecorders')]
    public function test_impersonal_and_new_verb_grade_reports_get_guidance_for_own_module(R $role, string $url): void
    {
        $this->login($role);
        foreach (['No se guardan las calificaciones', 'No puedo meter las calificaciones', 'No me deja que registre las calificaciones'] as $message) {
            $this->postJson(route('chatbot.message'), ['message' => $message])
                ->assertOk()->assertJsonPath('status', 'success')->assertJsonPath('code', 'GUIDANCE_READY')
                ->assertJsonCount(3, 'data.items')->assertJsonPath('actions.0.url', $url);
        }
    }

    #[DataProvider('nonRecorderRoles')]
    public function test_impersonal_grade_report_is_denied_for_non_recorders(R $role): void
    {
        $this->login($role);
        $response = $this->postJson(route('chatbot.message'), ['message' => 'No se guardan las calificaciones'])
            ->assertOk()->assertJsonPath('status', 'denied')->assertJsonPath('code', 'ROLE_NOT_ALLOWED')
            ->assertJsonPath('data.items', [])->assertJsonPath('actions', []);
        $this->assertStringNotContainsString('Acude a Secretaría.', $response->json('message'));
    }

    public function test_catechist_without_group_in_period_is_told_to_check_period_or_ask_secretary(): void
    {
        $context = $this->login();
        $this->records($context, period: 2);
        $this->postJson(route('chatbot.message'), ['message' => 'No tengo grupo'])
            ->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(0, 'data.items')
            ->assertJsonPath('message', 'No tienes grupos asignados en el periodo actual. Comprueba que sea el periodo correcto o solicita tu asignación a Secretaría.');
    }

    /** Textos de lista vacía por caso y rol: [caso => [rol => texto]]. */
    private const EMPTY_TEXTS = [
        'assignments' => [
            'catequista' => 'No tienes asignaciones en el periodo actual. Comprueba que sea el periodo correcto o solicita tu asignación a Secretaría.',
            'coordinador_comunidades' => 'Tu comunidad no tiene asignaciones en el periodo actual. Comprueba que sea el periodo correcto o consulta a Secretaría.',
            'secretaria' => 'No hay asignaciones en el periodo actual. Comprueba que sea el periodo correcto o regístralas en Asignar grupos.',
            'parroco' => 'No hay asignaciones en el periodo actual. Comprueba que sea el periodo correcto o consulta a Secretaría.',
            'coordinador_general' => 'No hay asignaciones en el periodo actual. Comprueba que sea el periodo correcto o consulta a Secretaría.',
        ],
        'groups' => [
            'catequista' => 'No tienes grupos asignados en el periodo actual. Comprueba que sea el periodo correcto o solicita tu asignación a Secretaría.',
            'coordinador_comunidades' => 'Tu comunidad no tiene grupos asignados en el periodo actual. Comprueba que sea el periodo correcto o consulta a Secretaría.',
            'secretaria' => 'No hay grupos asignados en el periodo actual. Comprueba que sea el periodo correcto o asígnalos en Asignar grupos.',
            'parroco' => 'No hay grupos asignados en el periodo actual. Comprueba que sea el periodo correcto o consulta a Secretaría.',
            'coordinador_general' => 'No hay grupos asignados en el periodo actual. Comprueba que sea el periodo correcto o consulta a Secretaría.',
        ],
        'students' => [
            'catequista' => 'Este grupo no tiene alumnos inscritos en el periodo actual. Si falta alguno, solicita su inscripción a Secretaría.',
            'coordinador_comunidades' => 'Este grupo no tiene alumnos inscritos en el periodo actual. Si falta alguno, consulta a Secretaría.',
            'secretaria' => 'Este grupo no tiene alumnos inscritos en el periodo actual. Puedes inscribirlos en Inscripciones.',
            'parroco' => 'Este grupo no tiene alumnos inscritos en el periodo actual. Si falta alguno, consulta a Secretaría.',
            'coordinador_general' => 'Este grupo no tiene alumnos inscritos en el periodo actual. Si falta alguno, consulta a Secretaría.',
        ],
        'evaluations' => [
            'catequista' => 'Aún no hay calificaciones registradas para esta selección. Puedes capturarlas en Evaluaciones.',
            'coordinador_comunidades' => 'Aún no hay calificaciones registradas para esta selección. Las registran el catequista del grupo y Secretaría.',
            'secretaria' => 'Aún no hay calificaciones registradas para esta selección. Puedes capturarlas en Evaluaciones.',
            'parroco' => 'Aún no hay calificaciones registradas para esta selección. Las registran el catequista del grupo y Secretaría.',
            'coordinador_general' => 'Aún no hay calificaciones registradas para esta selección. Las registran el catequista del grupo y Secretaría.',
        ],
    ];

    /** Asignación válida del periodo 1 sin inscripciones (grupo vacío). */
    private function emptyAssignment(\App\Services\Authorization\AccessContext $context): int
    {
        $group = DB::table('grupos')->insertGetId(['periodo_id' => 1]);

        return DB::table('asigna_grupo')->insertGetId([
            'catequista_id' => $context->userId, 'comunidad_id' => 1, 'grupo_id' => $group, 'nivel_id' => 1, 'periodo_id' => 1,
        ]);
    }

    #[DataProvider('roles')]
    public function test_empty_results_have_exact_text_for_each_role(R $role): void
    {
        $context = $this->login($role);
        // Sin asignaciones visibles en el periodo 1: existen en el periodo 2 y, para los alcances acotados,
        // también de otro catequista en otra comunidad (Secretaría y supervisión sí las verían).
        $this->records($context, period: 2);
        if ($role === R::Catequista || $role === R::CoordinadorComunidades) {
            $this->records($this->context(), community: 2);
        }
        $this->postJson(route('chatbot.message'), ['message' => 'mis alumnos'])
            ->assertOk()->assertJsonPath('status', 'selection_required')->assertJsonCount(0, 'data.options')
            ->assertJsonPath('message', self::EMPTY_TEXTS['assignments'][$role->value]);
        if ($role === R::Catequista || $role === R::CoordinadorComunidades) {
            $this->postJson(route('chatbot.message'), ['message' => 'No tengo grupo'])
                ->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(0, 'data.items')
                ->assertJsonPath('message', self::EMPTY_TEXTS['groups'][$role->value]);
        }
        $assignment = $this->emptyAssignment($context);
        $this->postJson(route('chatbot.message'), ['message' => 'mis alumnos', 'assignment_id' => $assignment])
            ->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(0, 'data.items')
            ->assertJsonPath('message', self::EMPTY_TEXTS['students'][$role->value]);
        $this->postJson(route('chatbot.message'), ['message' => 'muéstrame evaluaciones', 'assignment_id' => $assignment])
            ->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(0, 'data.items')
            ->assertJsonPath('message', self::EMPTY_TEXTS['evaluations'][$role->value]);
    }

    #[DataProvider('roles')]
    public function test_empty_groups_text_for_each_role_when_period_has_no_assignments(R $role): void
    {
        $context = $this->login($role);
        $this->records($context, period: 2);
        $this->postJson(route('chatbot.message'), ['message' => 'No tengo grupos asignados'])
            ->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(0, 'data.items')
            ->assertJsonPath('message', self::EMPTY_TEXTS['groups'][$role->value]);
    }

    #[DataProvider('roles')]
    public function test_non_empty_results_keep_their_current_text(R $role): void
    {
        $context = $this->login($role);
        $ids = $this->records($context);
        $groups = match ($role) {
            R::Catequista => 'Estas son tus asignaciones disponibles.',
            R::CoordinadorComunidades => 'Estas son las asignaciones de tu comunidad en el periodo actual.',
            default => 'Estas son las asignaciones del periodo actual.',
        };
        $this->postJson(route('chatbot.message'), ['message' => 'mis grupos'])
            ->assertOk()->assertJsonCount(1, 'data.items')->assertJsonPath('message', $groups);
        $this->postJson(route('chatbot.message'), ['message' => 'mis alumnos'])
            ->assertOk()->assertJsonPath('message', 'Selecciona una asignación para continuar.');
        $this->postJson(route('chatbot.message'), ['message' => 'mis alumnos', 'assignment_id' => $ids['assignment']])
            ->assertOk()->assertJsonCount(1, 'data.items')->assertJsonPath('message', 'Estos son los alumnos de la asignación seleccionada.');
    }

    #[DataProvider('roles')]
    public function test_scope_is_never_serialized_in_json(R $role): void
    {
        $context = $this->login($role);
        $ids = $this->records($context);
        $assignment = $this->emptyAssignment($context);
        foreach ([['message' => 'mis grupos'], ['message' => 'mis alumnos'], ['message' => 'mis alumnos', 'assignment_id' => $assignment],
            ['message' => 'muéstrame evaluaciones', 'assignment_id' => $ids['assignment']], ['message' => 'No tengo grupo'], ['message' => 'ayuda']] as $payload) {
            $response = $this->postJson(route('chatbot.message'), $payload)->assertOk()
                ->assertJsonMissingPath('meta.scope')->assertJsonMissingPath('scope');
            $this->assertSame(['hasMore'], array_keys($response->json('meta')));
            foreach (['scope', 'own', 'community', 'manager', 'supervisor'] as $leak) {
                $this->assertStringNotContainsString('"'.$leak.'"', $response->getContent());
            }
        }
    }

    public function test_catechist_lacking_group_report_lists_only_own_assignments(): void
    {
        $context = $this->login();
        $own = $this->records($context);
        $other = $this->records($this->context());
        DB::table('grupos')->where('id', $own['group'])->update(['nombre' => 'Grupo propio']);
        DB::table('grupos')->where('id', $other['group'])->update(['nombre' => 'Grupo ajeno']);
        $this->postJson(route('chatbot.message'), ['message' => 'No tengo grupos asignados'])
            ->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(1, 'data.items')
            ->assertSee('Grupo propio')->assertDontSee('Grupo ajeno');
        $this->postJson(route('chatbot.message'), ['message' => 'No tengo alumnos'])
            ->assertOk()->assertJsonPath('status', 'selection_required')->assertJsonCount(1, 'data.options')
            ->assertJsonPath('data.options.0.id', $own['assignment']);
    }

    public function test_community_coordinator_impersonal_students_report_sees_only_own_community(): void
    {
        $context = $this->login(R::CoordinadorComunidades);
        $own = $this->records($context);
        $this->records($context, community: 2);
        $this->postJson(route('chatbot.message'), ['message' => 'No se ven mis alumnos'])
            ->assertOk()->assertJsonPath('status', 'selection_required')->assertJsonCount(1, 'data.options')
            ->assertJsonPath('data.options.0.id', $own['assignment']);
    }

    public function test_general_coordinator_impersonal_grades_report_gets_selection(): void
    {
        $this->login(R::CoordinadorGeneral);
        $ids = $this->records($this->context());
        $this->postJson(route('chatbot.message'), ['message' => 'No se cargan las evaluaciones'])
            ->assertOk()->assertJsonPath('status', 'selection_required')->assertJsonPath('data.options.0.id', $ids['assignment']);
        $this->postJson(route('chatbot.message'), ['message' => 'No se cargan las evaluaciones', 'assignment_id' => $ids['assignment']])
            ->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(1, 'data.items');
    }

    /** [frase, código, enlace esperado para Secretaría o null]. */
    public static function blockedImpersonalWrites(): array
    {
        $grade = ['GRADE_WRITE_UNSUPPORTED', '/secretaria/evaluaciones'];

        return [['no se registra la calificación', ...$grade], ['no se pone la calificación', ...$grade],
            ['no se guarda la asistencia', 'ATTENDANCE_WRITE_UNSUPPORTED', null], ['mete las calificaciones de mi grupo', ...$grade],
            ['No funciona', 'UNKNOWN_INTENT', null], ['No me sale nada', 'UNKNOWN_INTENT', null]];
    }

    #[DataProvider('blockedImpersonalWrites')]
    public function test_blocked_or_unrecognized_reports_stay_unsupported_without_data(string $message, string $code, ?string $url): void
    {
        $this->login(R::Secretaria);
        $this->postJson(route('chatbot.message'), ['message' => $message])
            ->assertOk()->assertJsonPath('status', 'unsupported')->assertJsonPath('code', $code)
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('actions', $url ? [['type' => 'navigate', 'label' => 'Abrir módulo', 'url' => $url]] : []);
    }

    #[DataProvider('evaluationRecorders')]
    public function test_new_grade_write_commands_stay_unsupported_without_data(R $role, string $url): void
    {
        $context = $this->login($role);
        $this->records($context);
        foreach (['carga las calificaciones', 'ingresa las calificaciones', 'ponle 9 de calificación a Juan', 'registren las calificaciones'] as $message) {
            $this->postJson(route('chatbot.message'), ['message' => $message])
                ->assertOk()->assertJsonPath('status', 'unsupported')->assertJsonPath('code', 'GRADE_WRITE_UNSUPPORTED')
                ->assertJsonPath('data.items', [])->assertJsonPath('data.options', [])
                ->assertJsonPath('actions', [['type' => 'navigate', 'label' => 'Abrir módulo', 'url' => $url]]);
        }
        $this->postJson(route('chatbot.message'), ['message' => 'No puedo ingresar las calificaciones'])
            ->assertOk()->assertJsonPath('code', 'GUIDANCE_READY')->assertJsonPath('actions.0.url', $url);
    }

    public function test_grade_display_report_with_cargan_still_gets_selection(): void
    {
        $context = $this->login();
        $own = $this->records($context);
        $this->postJson(route('chatbot.message'), ['message' => 'no me cargan las evaluaciones'])
            ->assertOk()->assertJsonPath('status', 'selection_required')->assertJsonPath('data.options.0.id', $own['assignment']);
    }

    /** Matriz frase × rol con el texto exacto: los mensajes nuevos solo llegan al rol que corresponde. */
    public static function orientedResponses(): array
    {
        $denied = 'No tienes acceso a esta solicitud o al recurso seleccionado. Acude a Secretaría.';
        $edit = 'El asistente no modifica datos. Edita el alumno desde el módulo Alumnos.';
        $change = 'El asistente no modifica datos. Realiza el cambio desde el módulo correspondiente.';
        $escalate = 'Esta operación requiere atención en el módulo correspondiente. Acude a Secretaría.';
        $boleta = 'Las boletas las genera Secretaría. Solicítale la boleta del alumno.';
        $attendance = 'La asistencia se toma en papel con la lista que cada catequista descarga desde Mi grupo. El sistema no la registra.';
        $select = 'Selecciona una asignación para continuar.';
        $grades = 'Las calificaciones las registran el catequista del grupo y Secretaría en el módulo Evaluaciones. Para una corrección, acude a ellos.';
        $guidance = 'Sigue estos pasos en el módulo del sistema. No realizaré cambios desde el chat.';
        $boletaModule = 'Las boletas se generan en el módulo Boletas: filtra por grupo y elige al alumno.';
        $paperOwn = 'La asistencia se toma en papel con la lista que descargas desde Mi grupo. El sistema no la registra.';
        $gradesInModule = 'El asistente no registra calificaciones. Puedes hacerlo en Evaluaciones.';
        $gradesNotRecorded = 'El asistente no registra calificaciones. Las registran el catequista del grupo y Secretaría.';
        $supervisors = [R::Parroco, R::CoordinadorGeneral, R::CoordinadorComunidades];
        $boletaUrl = [R::Parroco->value => '/parroco/boletas', R::CoordinadorGeneral->value => '/coordinador-general/boletas',
            R::CoordinadorComunidades->value => '/coordinador-comunidades/boletas'];
        $cases = [
            'editar: secretaria' => ['No me deja editar un alumno', R::Secretaria, 'unsupported', 'EXECUTION_UNSUPPORTED', $edit, '/secretaria/alumnos'],
            // Punto 1: asistencia bloqueada; solo el catequista recibe enlace a Mi grupo.
            'asistencia bloqueada: catequista' => ['pasen la lista de asistencia', R::Catequista, 'unsupported', 'ATTENDANCE_WRITE_UNSUPPORTED', $paperOwn, '/catequista/mi-grupo'],
            'asistencia bloqueada: secretaria' => ['pasen la lista de asistencia', R::Secretaria, 'unsupported', 'ATTENDANCE_WRITE_UNSUPPORTED', $attendance],
            // Punto 2: calificaciones bloqueadas; Catequista y Secretaría reciben enlace a su propio módulo.
            'calificaciones bloqueadas: catequista' => ['registra calificaciones', R::Catequista, 'unsupported', 'GRADE_WRITE_UNSUPPORTED', $gradesInModule, '/catequista/evaluaciones'],
            'calificaciones bloqueadas: secretaria' => ['registra calificaciones', R::Secretaria, 'unsupported', 'GRADE_WRITE_UNSUPPORTED', $gradesInModule, '/secretaria/evaluaciones'],
            // Punto 5: grupos con datos según el alcance.
            'grupos: catequista' => ['mis grupos', R::Catequista, 'success', 'VIEW_MY_GROUPS_READY', 'Estas son tus asignaciones disponibles.'],
            'grupos: secretaria' => ['mis grupos', R::Secretaria, 'success', 'VIEW_MY_GROUPS_READY', 'Estas son las asignaciones del periodo actual.'],
            'editar: catequista' => ['No me deja editar un alumno', R::Catequista, 'denied', 'ROLE_NOT_ALLOWED', $denied],
            'escalar: secretaria' => ['quiero corregir información administrativa', R::Secretaria, 'denied', 'ADMINISTRATIVE_EXECUTION_UNSUPPORTED', $change],
            'escalar: catequista' => ['quiero corregir información administrativa', R::Catequista, 'denied', 'ADMINISTRATIVE_EXECUTION_UNSUPPORTED', $escalate],
            'boleta: catequista' => ['descargar boleta', R::Catequista, 'denied', 'ROLE_NOT_ALLOWED', $boleta],
            'boleta: secretaria' => ['descargar boleta', R::Secretaria, 'missing_context', 'MISSING_RESOURCE', $boletaModule, '/secretaria/boletas'],
            'asistencia: catequista' => ['lista de asistencia', R::Catequista, 'selection_required', 'ASSIGNMENT_SELECTION_REQUIRED', $select],
            'asistencia: secretaria' => ['lista de asistencia', R::Secretaria, 'denied', 'ROLE_NOT_ALLOWED', $attendance],
            'calificaciones: catequista' => ['¿Cómo registro calificaciones?', R::Catequista, 'success', 'GUIDANCE_READY', $guidance],
            'calificaciones: secretaria' => ['¿Cómo registro calificaciones?', R::Secretaria, 'success', 'GUIDANCE_READY', $guidance],
        ];
        foreach ($supervisors as $role) {
            $cases['editar: '.$role->value] = ['No me deja editar un alumno', $role, 'denied', 'ROLE_NOT_ALLOWED', $denied];
            $cases['escalar: '.$role->value] = ['quiero corregir información administrativa', $role, 'denied', 'ADMINISTRATIVE_EXECUTION_UNSUPPORTED', $escalate];
            $cases['boleta: '.$role->value] = ['descargar boleta', $role, 'missing_context', 'MISSING_RESOURCE', $boletaModule, $boletaUrl[$role->value]];
            $cases['asistencia: '.$role->value] = ['lista de asistencia', $role, 'denied', 'ROLE_NOT_ALLOWED', $attendance];
            $cases['calificaciones: '.$role->value] = ['¿Cómo registro calificaciones?', $role, 'denied', 'ROLE_NOT_ALLOWED', $grades];
            $cases['asistencia bloqueada: '.$role->value] = ['pasen la lista de asistencia', $role, 'unsupported', 'ATTENDANCE_WRITE_UNSUPPORTED', $attendance];
            $cases['calificaciones bloqueadas: '.$role->value] = ['registra calificaciones', $role, 'unsupported', 'GRADE_WRITE_UNSUPPORTED', $gradesNotRecorded];
            $cases['grupos: '.$role->value] = ['mis grupos', $role, 'success', 'VIEW_MY_GROUPS_READY', $role === R::CoordinadorComunidades
                ? 'Estas son las asignaciones de tu comunidad en el periodo actual.' : 'Estas son las asignaciones del periodo actual.'];
        }

        return $cases;
    }

    #[DataProvider('orientedResponses')]
    public function test_oriented_responses_have_exact_text_per_role(string $message, R $role, string $status, string $code, string $text, ?string $url = null): void
    {
        $context = $this->login($role);
        $this->records($context);
        $response = $this->postJson(route('chatbot.message'), ['message' => $message])
            ->assertOk()->assertJsonPath('status', $status)->assertJsonPath('code', $code)->assertJsonPath('message', $text);
        if ($status !== 'success' && $status !== 'selection_required') {
            $this->assertSame([], $response->json('data.items'));
        }
        // El enlace solo aparece para el rol indicado en el caso; el resto de rechazos y bloqueos no enlaza.
        $navigate = array_values(array_filter($response->json('actions'), fn ($a) => $a['type'] === 'navigate'));
        if ($url !== null) {
            $this->assertSame([['type' => 'navigate', 'label' => 'Abrir módulo', 'url' => $url]], $navigate);
        } elseif ($status !== 'success') {
            $this->assertSame([], $navigate, 'Este rol no debe recibir enlace en un rechazo o bloqueo.');
        }
    }

    #[DataProvider('roles')]
    public function test_reinforced_write_rules_respond_per_role(R $role): void
    {
        $context = $this->login($role);
        $own = $this->records($context);
        $module = match ($role) {
            R::Catequista => '/catequista/evaluaciones',
            R::Secretaria => '/secretaria/evaluaciones',
            default => null,
        };
        $navigate = fn (?string $url) => $url ? [['type' => 'navigate', 'label' => 'Abrir módulo', 'url' => $url]] : [];

        // Guía: preguntas impersonales, calificar a alumnos y subjuntivo con «ingrese».
        foreach (['¿Cómo se registran las calificaciones?', '¿Cómo califico a mis alumnos?', 'no me deja que ingrese las calificaciones'] as $message) {
            $response = $this->postJson(route('chatbot.message'), ['message' => $message])->assertOk();
            if ($module) {
                $response->assertJsonPath('status', 'success')->assertJsonPath('code', 'GUIDANCE_READY')
                    ->assertJsonCount(3, 'data.items')->assertJsonPath('actions', $navigate($module));
            } else {
                $response->assertJsonPath('status', 'denied')->assertJsonPath('code', 'ROLE_NOT_ALLOWED')
                    ->assertJsonPath('message', 'Las calificaciones las registran el catequista del grupo y Secretaría en el módulo Evaluaciones. Para una corrección, acude a ellos.')
                    ->assertJsonPath('data.items', [])->assertJsonPath('actions', []);
            }
        }

        // Bloqueo con explicación: «como» en otra parte de la frase y calificar a alumnos sin el sustantivo.
        foreach (['regístralas como calificaciones', 'califica a mis alumnos'] as $message) {
            $this->postJson(route('chatbot.message'), ['message' => $message])
                ->assertOk()->assertJsonPath('status', 'unsupported')->assertJsonPath('code', 'GRADE_WRITE_UNSUPPORTED')
                ->assertJsonPath('message', $module
                    ? 'El asistente no registra calificaciones. Puedes hacerlo en Evaluaciones.'
                    : 'El asistente no registra calificaciones. Las registran el catequista del grupo y Secretaría.')
                ->assertJsonPath('data.items', [])->assertJsonPath('actions', $navigate($module));
        }

        // «registro» como sustantivo: consulta de evaluaciones con la selección del propio alcance.
        $this->postJson(route('chatbot.message'), ['message' => 'ver el registro de calificaciones'])
            ->assertOk()->assertJsonPath('status', 'selection_required')->assertJsonPath('code', 'ASSIGNMENT_SELECTION_REQUIRED')
            ->assertJsonCount(1, 'data.options')->assertJsonPath('data.options.0.id', $own['assignment']);
    }

    public function test_message_mixing_both_write_guards_keeps_generic_response(): void
    {
        $this->login(R::Secretaria);
        $this->postJson(route('chatbot.message'), ['message' => 'registra la asistencia y registra las calificaciones'])
            ->assertOk()->assertJsonPath('status', 'unsupported')->assertJsonPath('code', 'UNKNOWN_INTENT')
            ->assertJsonPath('actions', [])->assertJsonPath('data.items', []);
    }

    public function test_catechist_grade_guidance_first_step_mentions_missing_group(): void
    {
        $this->login();
        $this->postJson(route('chatbot.message'), ['message' => '¿Cómo registro calificaciones?'])->assertOk()
            ->assertJsonPath('data.items.0', 'Entra a Evaluaciones y selecciona el grupo asignado y la unidad; si no aparece ningún grupo, comprueba que sea el periodo correcto o solicita tu asignación a Secretaría.')
            ->assertJsonCount(3, 'data.items');
    }

    public function test_secretary_grade_guidance_first_step_is_unchanged(): void
    {
        $this->login(R::Secretaria);
        $this->postJson(route('chatbot.message'), ['message' => '¿Cómo registro calificaciones?'])->assertOk()
            ->assertJsonPath('data.items.0', 'Entra a Evaluaciones y selecciona periodo, grupo y unidad.')
            ->assertJsonCount(3, 'data.items');
    }

    public function test_escalation_without_requester_role_keeps_secretary_referral(): void
    {
        // Ruta de revalidación (AgentGate) sin rol: conserva el texto y la canalización anteriores.
        $decision = new \App\Services\Chatbot\Rules\RuleResult(\App\Enums\ChatbotRuleResult::Escalate, 'ADMINISTRATIVE_EXECUTION_UNSUPPORTED', [], recommendedResponsible: 'secretaria');
        $response = \App\Services\Chatbot\Agents\EscalationAgent::response($decision);
        $this->assertSame('chatbot.contact_responsible', $response->messageKey);
        $this->assertSame([['type' => 'contact', 'responsible' => 'secretaria']], $response->suggestedActions);
        $self = \App\Services\Chatbot\Agents\EscalationAgent::response($decision, R::Secretaria);
        $this->assertSame('chatbot.change_in_module', $self->messageKey);
        $this->assertSame([], $self->suggestedActions);
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
