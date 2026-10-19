<?php

namespace Tests\Feature\Localization;

use App\Enums\UserRole as R;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CatequesisWebTestCase;

/** Los mensajes por defecto de validación, autenticación, contraseñas y paginación salen en español. */
class MensajesEnEspanolTest extends CatequesisWebTestCase
{
    public function test_tests_run_with_spanish_locale_and_english_fallback(): void
    {
        $this->assertSame('es', app()->getLocale());
        $this->assertSame('en', config('app.fallback_locale'));
    }

    #[DataProvider('langFiles')]
    public function test_spanish_files_have_the_same_keys_as_the_framework(string $file): void
    {
        $en = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/'.$file.'.php');
        $es = require lang_path('es/'.$file.'.php');
        unset($en['custom'], $en['attributes'], $es['custom'], $es['attributes']);

        $this->assertSame($this->keys($en), $this->keys($es));
    }

    public static function langFiles(): array
    {
        return [['validation'], ['auth'], ['passwords'], ['pagination']];
    }

    public function test_login_with_malformed_email_gets_spanish_message(): void
    {
        $this->from('/login')->post('/login', ['email' => 'no-es-correo', 'password' => 'x'])
            ->assertSessionHasErrors(['email' => 'El campo correo electrónico debe ser un correo electrónico válido.']);
    }

    public function test_registration_password_defaults_rule_gets_spanish_message(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'Usuario Prueba', 'email' => 'prueba@example.com',
            'password' => 'abc12', 'password_confirmation' => 'abc12',
            'requested_role' => 'catequista',
        ])->assertSessionHasErrors(['password' => 'El campo contraseña debe tener al menos 8 caracteres.']);

        $this->assertDatabaseMissing('users', ['email' => 'prueba@example.com']);
    }

    public function test_screen_without_own_messages_gets_spanish_message(): void
    {
        $context = $this->context(R::Catequista);
        $this->actingAs(User::findOrFail($context->userId));

        $this->from(route('catequista.dashboard'))->get(route('catequista.mi_grupo', ['asignacion_id' => 0]))
            ->assertRedirect(route('catequista.dashboard'))
            ->assertSessionHasErrors(['asignacion_id' => 'El campo asignación debe ser al menos 1.']);
    }

    public function test_login_throttle_message_is_spanish(): void
    {
        $user = User::factory()->create(['role' => 'catequista', 'status' => 'aprobado']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta']);
        }

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'incorrecta'])
            ->assertSessionHasErrors('email');

        $this->assertStringStartsWith(
            'Demasiados intentos de inicio de sesión. Inténtalo de nuevo en ',
            session('errors')->first('email'),
        );
        $this->assertStringEndsWith(' segundos.', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_wrong_password_confirmation_message_is_spanish(): void
    {
        $user = User::factory()->create(['role' => 'catequista', 'status' => 'aprobado']);

        $this->actingAs($user)->from('/confirm-password')->post('/confirm-password', ['password' => 'incorrecta'])
            ->assertSessionHasErrors(['password' => 'La contraseña es incorrecta.']);
    }

    public function test_pagination_labels_are_spanish(): void
    {
        $this->assertSame('Siguiente &raquo;', __('pagination.next'));
        $this->assertSame('&laquo; Anterior', __('pagination.previous'));

        foreach (range(3, 12) as $id) {
            DB::table('comunidades')->insert(['id' => $id, 'comunidad' => 'Comunidad '.$id]);
        }
        $context = $this->context(R::Secretaria);
        $this->actingAs(User::findOrFail($context->userId));

        $this->get(route('secretaria.comunidades.index', ['per_page' => 10]))
            ->assertOk()
            ->assertSee('Mostrando')
            ->assertSee('resultados')
            ->assertSee('Siguiente &raquo;', false)
            ->assertDontSee('Showing')
            ->assertDontSee('results');
    }

    public function test_own_controller_messages_take_priority_over_lang_files(): void
    {
        $this->from('/login')->post('/login', ['email' => 'nadie@example.com', 'password' => 'x'])
            ->assertSessionHasErrors(['email' => 'El correo electrónico no está registrado en el sistema.']);
    }

    /** Claves en notación de puntos, ordenadas, para comparar la estructura de dos archivos de idioma. */
    private function keys(array $lines, string $prefix = ''): array
    {
        $keys = [];
        foreach ($lines as $key => $value) {
            $keys[] = $prefix.$key;
            if (is_array($value)) {
                $keys = array_merge($keys, $this->keys($value, $prefix.$key.'.'));
            }
        }
        sort($keys);

        return $keys;
    }
}
