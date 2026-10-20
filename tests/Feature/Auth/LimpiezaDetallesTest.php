<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CatequesisWebTestCase;

/** Detalles pendientes: periodos en «Cambiar contraseña», registro con rol manipulado, código sin uso y alertas de acceso. */
class LimpiezaDetallesTest extends CatequesisWebTestCase
{
    /** Texto con comillas, barra invertida, &, etiquetas y salto de línea. */
    private const TRICKY = "Línea \"uno\" & <b>dos</b>\\tres\ncuatro";

    /** Opciones de @json en Blade. */
    private const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    #[DataProvider('rolesWithPeriodSelector')]
    public function test_change_password_page_lists_periods_in_the_period_window(string $role): void
    {
        $user = User::factory()->create(['role' => $role, 'status' => 'aprobado', 'comunidad_id' => 1]);

        $this->actingAs($user)->get('/cuenta/contrasena')->assertOk()
            ->assertSee('name="periodo_id" value="1"', false)
            ->assertSee('Enero 2026 - Diciembre 2026')
            ->assertDontSee('No hay periodos registrados');
    }

    public static function rolesWithPeriodSelector(): array
    {
        return [['secretaria'], ['catequista']];
    }

    public function test_registration_with_tampered_role_is_a_validation_error_not_a_success(): void
    {
        $this->postJson('/register', [
            'name' => 'Persona Prueba', 'email' => 'persona@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
            'requested_role' => 'administrador',
        ])->assertStatus(422)->assertJsonPath('errors.requested_role.0', 'Selecciona un tipo de acceso válido.');

        $this->assertDatabaseMissing('users', ['email' => 'persona@example.com']);
    }

    public function test_unused_views_layouts_and_components_are_gone(): void
    {
        foreach (['admin.usuarios.pendientes', 'components.auth-session-status', 'layouts.app', 'layouts.app_bootstrap', 'layouts.app_parroquia'] as $view) {
            $this->assertFalse(view()->exists($view), $view);
        }
        $this->assertFileDoesNotExist(app_path('View/Components/AppLayout.php'));
        $this->assertDirectoryDoesNotExist(resource_path('views/admin'));
        // Lo que sí se usa se conserva.
        $this->assertTrue(view()->exists('layouts.guest'));
        $this->assertFileExists(app_path('View/Components/GuestLayout.php'));
    }

    #[DataProvider('accessAlerts')]
    public function test_access_pages_put_alert_texts_in_javascript_as_json(string $url, string $kind): void
    {
        $session = $kind === 'status'
            ? ['status' => self::TRICKY]
            : ['errors' => (new ViewErrorBag)->put('default', new MessageBag(['email' => [self::TRICKY]]))];

        $this->withSession($session)->get($url)->assertOk()
            ->assertSee('text: '.json_encode(self::TRICKY, self::JSON_FLAGS), false)
            ->assertDontSee('&quot;uno&quot;', false);
    }

    public static function accessAlerts(): array
    {
        return [
            'login, error' => ['/login', 'error'],
            'login, aviso' => ['/login', 'status'],
            'olvidé la contraseña, error' => ['/forgot-password', 'error'],
            'olvidé la contraseña, aviso' => ['/forgot-password', 'status'],
            'restablecer, error' => ['/reset-password/token-de-prueba?email=a@example.com', 'error'],
        ];
    }

    public function test_forgot_password_limit_alert_has_its_own_title(): void
    {
        config(['auth.timebox_duration' => 0]);
        Notification::fake();
        for ($i = 1; $i <= 20; $i++) {
            $this->from('/forgot-password')->post('/forgot-password', ['email' => "solicitud{$i}@example.com"]);
        }
        $this->flushSession();

        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'otra@example.com'])
            ->assertRedirect(route('password.request'));

        $this->get('/forgot-password')->assertOk()
            ->assertSee('title: '.json_encode('Demasiadas solicitudes', self::JSON_FLAGS), false);
    }

    public function test_forgot_password_validation_errors_keep_the_generic_title(): void
    {
        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'no-es-correo']);

        $this->get('/forgot-password')->assertOk()
            ->assertSee('title: '.json_encode('Error de validación', self::JSON_FLAGS), false);
    }
}
