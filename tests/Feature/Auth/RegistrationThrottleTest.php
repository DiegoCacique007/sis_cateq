<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\Support\AuthTestCase;

/** El registro limita los intentos por IP y por correo, y sus mensajes no confirman qué datos existen. */
class RegistrationThrottleTest extends AuthTestCase
{
    private const LIMITED = 'Demasiados intentos de registro. Espera unos minutos e inténtalo de nuevo.';

    public function test_ip_limit_allows_40_attempts_every_10_minutes(): void
    {
        for ($i = 1; $i <= 40; $i++) {
            $this->postJson('/register', $this->invalidPayload("persona{$i}@example.com"))->assertStatus(422);
        }

        $this->postJson('/register', $this->invalidPayload('persona41@example.com'))
            ->assertStatus(429)
            ->assertJsonPath('message', self::LIMITED)
            ->assertJsonPath('errors.email.0', self::LIMITED);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_email_limit_applies_even_when_the_ip_changes(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$i}"])
                ->postJson('/register', $this->invalidPayload('mismo@example.com'))->assertStatus(422);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->postJson('/register', $this->invalidPayload('mismo@example.com'))
            ->assertStatus(429)
            ->assertJsonPath('errors.email.0', self::LIMITED);
    }

    public function test_other_people_on_the_same_network_can_still_register(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $this->postJson('/register', $this->invalidPayload('persona.a@example.com'));
        }

        $this->postJson('/register', $this->validPayload('Persona B', 'persona.b@example.com'))
            ->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['email' => 'persona.b@example.com', 'status' => 'pendiente']);
    }

    public function test_limit_expires_after_the_10_minute_window(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/register', $this->invalidPayload('espera@example.com'));
        }
        $this->postJson('/register', $this->invalidPayload('espera@example.com'))->assertStatus(429);

        $this->travel(11)->minutes();

        $this->postJson('/register', $this->invalidPayload('espera@example.com'))->assertStatus(422);
    }

    public function test_form_post_without_javascript_returns_to_register_with_message_and_email_only(): void
    {
        $payload = ['name' => 'Persona', 'email' => 'formulario@example.com', 'password' => 'abc', 'password_confirmation' => 'abc', 'requested_role' => 'catequista'];
        for ($i = 1; $i <= 5; $i++) {
            $this->from('/register')->post('/register', $payload);
        }
        $this->flushSession();

        $this->from('/register')->post('/register', $payload)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['email' => self::LIMITED]);
        $this->assertSame(['email' => 'formulario@example.com'], session()->getOldInput());
    }

    public function test_unique_messages_do_not_confirm_that_the_data_exists(): void
    {
        User::factory()->create(['name' => 'Persona Registrada', 'email' => 'registrada@example.com']);

        $this->postJson('/register', $this->validPayload('Persona Registrada', 'otra@example.com'))
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'No es posible registrar este nombre. Si ya tienes una cuenta, inicia sesión; si no, contacta a Secretaría.');

        $this->postJson('/register', $this->validPayload('Otra Persona', 'registrada@example.com'))
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'No es posible registrar este correo electrónico. Si ya tienes una cuenta, inicia sesión o recupera tu contraseña.');
    }

    public function test_selection_rules_use_gender_neutral_message(): void
    {
        foreach (['in:1,2', 'not_in:99', Rule::enum(UserRole::class), 'exists:users,id'] as $rule) {
            $message = Validator::make(['comunidad_id' => 99], ['comunidad_id' => [$rule]])->errors()->first('comunidad_id');

            $this->assertSame('La opción seleccionada en el campo comunidad no es válida.', $message);
        }
    }

    public function test_register_page_handles_the_429_response(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('response.status === 429', false)
            ->assertSee("'Demasiados intentos'", false);
    }

    /** Falta la contraseña: la validación lo rechaza sin crear la cuenta. */
    private function invalidPayload(string $email): array
    {
        return ['name' => 'Persona', 'email' => $email, 'requested_role' => 'catequista'];
    }

    private function validPayload(string $name, string $email): array
    {
        return ['name' => $name, 'email' => $email, 'password' => 'password', 'password_confirmation' => 'password', 'requested_role' => 'catequista'];
    }
}
