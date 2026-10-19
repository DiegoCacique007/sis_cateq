<?php

namespace Tests\Feature\Authorization;

use App\Http\Middleware\EnsureUserApproved;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\Support\CatequesisWebTestCase;

/** Protecciones de la gestión de cuentas de Secretaría y escape de nombres en «Usuarios pendientes». */
class UsuariosSeguridadTest extends CatequesisWebTestCase
{
    private const MALICIOUS_NAME = "Ana');alert(1);//\"<script>alert(2)</script>";

    public function test_secretaria_cannot_block_her_own_account(): void
    {
        $secretaria = $this->secretaria();

        $this->actingAs($secretaria)->from(route('secretaria.usuarios.pendientes'))
            ->post(route('secretaria.usuarios.bloquear', $secretaria))
            ->assertRedirect(route('secretaria.usuarios.pendientes'))
            ->assertSessionHas('error', 'No puedes bloquear tu propia cuenta.');

        $this->assertSame('aprobado', $secretaria->fresh()->status);
    }

    public function test_secretaria_cannot_change_her_own_role_through_approval(): void
    {
        $secretaria = $this->secretaria();

        $this->actingAs($secretaria)->from(route('secretaria.usuarios.pendientes'))
            ->post(route('secretaria.usuarios.aprobar', $secretaria), ['role' => 'catequista'])
            ->assertSessionHas('error', 'Solo se pueden aprobar solicitudes pendientes.');

        $this->assertSame('secretaria', $secretaria->fresh()->role);
        $this->assertSame('aprobado', $secretaria->fresh()->status);
    }

    public function test_the_last_approved_secretaria_can_never_be_blocked(): void
    {
        // Simula la carrera: B acaba de bloquear a A y, en la petición de A, A intenta bloquear a B.
        $this->withoutMiddleware(EnsureUserApproved::class);
        $a = $this->secretaria();
        $b = $this->secretaria();
        $a->forceFill(['status' => 'bloqueado'])->save();

        $this->actingAs($a)->from(route('secretaria.usuarios.pendientes'))
            ->post(route('secretaria.usuarios.bloquear', $b))
            ->assertSessionHas('error', 'No se puede bloquear: debe quedar al menos una cuenta de Secretaría aprobada.');

        $this->assertSame('aprobado', $b->fresh()->status);
        $this->assertSame(1, User::where('role', 'secretaria')->where('status', 'aprobado')->count());
    }

    public function test_with_two_secretarias_one_can_block_the_other(): void
    {
        $a = $this->secretaria();
        $b = $this->secretaria();

        $this->actingAs($a)->from(route('secretaria.usuarios.pendientes'))
            ->post(route('secretaria.usuarios.bloquear', $b))
            ->assertRedirect(route('secretaria.usuarios.pendientes'));

        $this->assertSame('bloqueado', $b->fresh()->status);
        $this->assertSame('aprobado', $a->fresh()->status);
    }

    public function test_approve_only_works_on_pending_accounts(): void
    {
        $secretaria = $this->secretaria();
        $approved = User::factory()->create(['role' => 'catequista', 'status' => 'aprobado']);
        $blocked = User::factory()->create(['role' => 'catequista', 'status' => 'bloqueado']);

        foreach ([$approved, $blocked] as $user) {
            $this->actingAs($secretaria)->from(route('secretaria.usuarios.pendientes'))
                ->post(route('secretaria.usuarios.aprobar', $user), ['role' => 'secretaria'])
                ->assertRedirect(route('secretaria.usuarios.pendientes'))
                ->assertSessionHas('error', 'Solo se pueden aprobar solicitudes pendientes.');
        }

        $this->assertSame(['catequista', 'aprobado'], [$approved->fresh()->role, $approved->fresh()->status]);
        $this->assertSame(['catequista', 'bloqueado'], [$blocked->fresh()->role, $blocked->fresh()->status]);
    }

    public function test_blocked_account_with_open_session_loses_access_including_chatbot(): void
    {
        $context = $this->context();
        $user = User::findOrFail($context->userId);
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('catequista.dashboard'));

        User::whereKey($user->id)->update(['status' => 'bloqueado']);
        Auth::forgetGuards();
        $this->actingAs($user->fresh());

        $this->postJson(route('chatbot.message'), ['message' => 'mis grupos'])
            ->assertForbidden()
            ->assertJsonPath('code', 'ACCESS_DENIED');
        $this->assertGuest();

        $this->actingAs($user->fresh())->get(route('catequista.dashboard'))->assertForbidden();
        $this->assertGuest();
    }

    public function test_blocked_account_with_remember_cookie_and_no_session_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'catequista', 'status' => 'aprobado', 'remember_token' => 'token-recordarme-de-prueba']);
        User::whereKey($user->id)->update(['status' => 'bloqueado']);
        $recaller = Auth::guard('web')->getRecallerName();

        $response = $this->withCookie($recaller, $user->id.'|token-recordarme-de-prueba|'.$user->getAuthPassword())
            ->get('/dashboard');

        $response->assertForbidden()->assertCookieExpired($recaller);
        $this->assertGuest();
        $this->assertNotSame('token-recordarme-de-prueba', $user->fresh()->remember_token);
    }

    public function test_pending_page_never_puts_user_names_inside_javascript(): void
    {
        User::factory()->create(['name' => self::MALICIOUS_NAME, 'role' => 'usuario', 'status' => 'pendiente', 'requested_role' => 'catequista']);

        $html = $this->actingAs($this->secretaria())->get(route('secretaria.usuarios.pendientes'))->assertOk()->getContent();

        preg_match_all('/\son[a-z]+\s*=\s*"([^"]*)"/i', $html, $handlers);
        foreach ($handlers[1] as $handler) {
            $this->assertStringNotContainsString('alert(', html_entity_decode($handler, ENT_QUOTES | ENT_HTML5));
        }
        preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $html, $scripts);
        foreach ($scripts[1] as $script) {
            $this->assertStringNotContainsString('alert(1)', $script);
            $this->assertStringNotContainsString('alert(2)', $script);
        }
        // El nombre se muestra escapado, como dato y no como código.
        $this->assertStringContainsString(e(self::MALICIOUS_NAME), $html);
        $this->assertStringNotContainsString('<script>alert(2)</script>', $html);
    }

    public function test_approving_a_community_coordinator_requires_a_community(): void
    {
        $secretaria = $this->secretaria();
        $pending = User::factory()->create(['role' => 'usuario', 'status' => 'pendiente', 'requested_role' => 'coordinador_comunidades']);
        $this->actingAs($secretaria)->get(route('secretaria.usuarios.pendientes'))
            ->assertOk()->assertSee('name="comunidad_id"', false);

        $this->from(route('secretaria.usuarios.pendientes'))
            ->post(route('secretaria.usuarios.aprobar', $pending), ['role' => 'coordinador_comunidades'])
            ->assertSessionHasErrors(['comunidad_id' => 'Selecciona la comunidad que coordinará.']);
        DB::table('comunidades')->where('id', 2)->update(['deleted_at' => now()]);
        $this->from(route('secretaria.usuarios.pendientes'))
            ->post(route('secretaria.usuarios.aprobar', $pending), ['role' => 'coordinador_comunidades', 'comunidad_id' => 2])
            ->assertSessionHasErrors(['comunidad_id' => 'La opción seleccionada en el campo comunidad no es válida.']);
        $this->assertSame('pendiente', $pending->fresh()->status);

        $this->from(route('secretaria.usuarios.pendientes'))
            ->post(route('secretaria.usuarios.aprobar', $pending), ['role' => 'coordinador_comunidades', 'comunidad_id' => 1])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $pending->id, 'role' => 'coordinador_comunidades', 'status' => 'aprobado',
            'comunidad_id' => 1, 'approved_by' => $secretaria->id,
        ]);
    }

    private function secretaria(): User
    {
        return User::factory()->create(['role' => 'secretaria', 'status' => 'aprobado']);
    }
}
