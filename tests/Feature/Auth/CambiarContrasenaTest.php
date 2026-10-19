<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CatequesisWebTestCase;

/** Cada usuario aprobado cambia su propia contraseña desde su layout; el perfil de Breeze ya no existe. */
class CambiarContrasenaTest extends CatequesisWebTestCase
{
    private const PAGE = '/cuenta/contrasena';

    public static function roles(): array
    {
        return [
            'secretaria' => ['secretaria', 'secretaria.dashboard'],
            'catequista' => ['catequista', 'catequista.dashboard'],
            'parroco' => ['parroco', 'parroco.dashboard'],
            'coordinador_general' => ['coordinador_general', 'coordinador_general.dashboard'],
            'coordinador_comunidades' => ['coordinador_comunidades', 'coordinador_comunidades.dashboard'],
        ];
    }

    #[DataProvider('roles')]
    public function test_page_renders_in_each_role_layout_with_only_password_fields(string $role, string $dashboard): void
    {
        $this->actingAs($this->user($role))->get(self::PAGE)
            ->assertOk()
            ->assertSee(route($dashboard), false)
            ->assertSee('name="current_password"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertDontSee('name="email"', false)
            ->assertDontSee('name="name"', false)
            ->assertDontSee('Eliminar cuenta');
    }

    #[DataProvider('roles')]
    public function test_each_layout_links_to_change_password(string $role, string $dashboard): void
    {
        $this->actingAs($this->user($role))->get(self::PAGE)
            ->assertOk()
            ->assertSee('href="'.url(self::PAGE).'"', false)
            ->assertSee('Cambiar contraseña');
    }

    public function test_password_is_changed_and_remember_token_rotated(): void
    {
        $user = $this->user();
        $token = $user->remember_token;

        $this->actingAs($user)->from(self::PAGE)->put('/password', $this->payload())
            ->assertRedirect(self::PAGE)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Tu contraseña se actualizó correctamente.');

        $user->refresh();
        $this->assertTrue(Hash::check('nueva-clave-segura', $user->password));
        $this->assertNotSame($token, $user->remember_token);
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = $this->user();

        $this->actingAs($user)->from(self::PAGE)->put('/password', $this->payload(['current_password' => 'incorrecta']))
            ->assertRedirect(self::PAGE)
            ->assertSessionHasErrors(['current_password' => 'La contraseña actual no es correcta.']);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    #[DataProvider('invalidNewPasswords')]
    public function test_new_password_rules_have_spanish_messages(array $override, string $message): void
    {
        $user = $this->user();

        $this->actingAs($user)->from(self::PAGE)->put('/password', $this->payload($override))
            ->assertRedirect(self::PAGE)
            ->assertSessionHasErrors(['password' => $message]);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public static function invalidNewPasswords(): array
    {
        return [
            'confirmación distinta' => [['password_confirmation' => 'otra-clave-segura'], 'La confirmación de la nueva contraseña no coincide.'],
            'demasiado corta' => [['password' => 'abc12', 'password_confirmation' => 'abc12'], 'La nueva contraseña debe tener al menos 8 caracteres.'],
            'igual a la actual' => [['password' => 'password', 'password_confirmation' => 'password'], 'La nueva contraseña debe ser diferente de la actual.'],
        ];
    }

    #[DataProvider('unapprovedStatuses')]
    public function test_unapproved_accounts_cannot_change_password(string $status): void
    {
        $user = User::factory()->create(['role' => 'catequista', 'status' => $status]);

        $this->actingAs($user)->from(self::PAGE)->put('/password', $this->payload())->assertForbidden();

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public static function unapprovedStatuses(): array
    {
        return [['pendiente'], ['bloqueado']];
    }

    public function test_other_sessions_are_closed_after_the_password_changes(): void
    {
        $user = $this->user();
        // Esta sesión es «el otro equipo»: guarda el hash de la contraseña con la que inició.
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('catequista.dashboard'));

        // La contraseña cambia desde otro lugar.
        $user->forceFill(['password' => Hash::make('cambiada-en-otro-equipo')])->save();

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_current_session_survives_its_own_password_change(): void
    {
        $user = $this->user();
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('catequista.dashboard'));

        $this->from('/dashboard')->put('/password', $this->payload())->assertSessionHasNoErrors();

        $this->get('/dashboard')->assertRedirect(route('catequista.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_breeze_profile_no_longer_exists(): void
    {
        $this->assertFileDoesNotExist(app_path('Http/Controllers/ProfileController.php'));
        $this->assertFileDoesNotExist(app_path('Http/Requests/ProfileUpdateRequest.php'));
        $this->assertFalse(view()->exists('profile.edit'));
        $this->assertFalse(view()->exists('layouts.navigation'));
        $this->assertFalse(view()->exists('dashboard'));

        $this->actingAs($this->user())->get('/profile')->assertNotFound();
    }

    public function test_password_update_is_limited_to_6_attempts_per_minute(): void
    {
        $user = $this->user();
        for ($i = 1; $i <= 6; $i++) {
            $this->actingAs($user)->from(self::PAGE)->put('/password', $this->payload(['current_password' => 'incorrecta']))
                ->assertRedirect(self::PAGE);
        }

        $this->actingAs($user)->from(self::PAGE)->put('/password', $this->payload())->assertStatus(429);
        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    private function user(string $role = 'catequista'): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'aprobado', 'comunidad_id' => 1]);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'current_password' => 'password',
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ], $override);
    }
}
