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

    public function test_parish_priest_can_read_but_cannot_register_student(): void
    {
        $context = $this->login(R::Parroco);
        $ids = $this->records($context);
        $this->postJson(route('chatbot.message'), ['message' => 'Muéstrame evaluaciones.', 'assignment_id' => $ids['assignment']])
            ->assertJsonPath('status', 'success')->assertJsonCount(1, 'data.items');
        $this->postJson(route('chatbot.message'), ['message' => '¿Cómo registro un alumno?'])->assertJsonPath('status', 'denied');
    }
}
