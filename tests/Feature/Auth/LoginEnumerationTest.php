<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AuthTestCase;

/** El login no revela si un correo está registrado; el estado de la cuenta solo se muestra con la contraseña correcta. */
class LoginEnumerationTest extends AuthTestCase
{
    private const GENERIC = 'El correo electrónico o la contraseña no son correctos.';

    public function test_unknown_email_and_wrong_password_get_exactly_the_same_response(): void
    {
        $user = User::factory()->create(['role' => 'catequista', 'status' => 'aprobado']);

        $unknown = $this->failedLogin('nadie@example.com', 'incorrecta');
        $this->flushSession();
        $wrongPassword = $this->failedLogin($user->email, 'incorrecta');

        $this->assertSame($unknown, $wrongPassword);
        $this->assertSame(['email' => [self::GENERIC]], $unknown['errors']);
        $this->assertSame(['email'], $unknown['old_input']);
        $this->assertGuest();
    }

    #[DataProvider('unapprovedAccounts')]
    public function test_account_state_is_not_revealed_with_a_wrong_password(string $status, string $role, string $message): void
    {
        $user = User::factory()->create(['role' => $role, 'status' => $status]);

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'incorrecta'])
            ->assertRedirect('/login');

        $this->assertSame(['email' => [self::GENERIC]], session('errors')->getBag('default')->toArray());
        $this->assertGuest();
    }

    #[DataProvider('unapprovedAccounts')]
    public function test_account_state_is_shown_only_with_the_correct_password(string $status, string $role, string $message): void
    {
        $user = User::factory()->create(['role' => $role, 'status' => $status]);

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/login');

        $this->assertSame(['email' => [$message]], session('errors')->getBag('default')->toArray());
        $this->assertGuest();
    }

    public static function unapprovedAccounts(): array
    {
        return [
            'pendiente' => ['pendiente', 'catequista', 'Tu cuenta está pendiente de aprobación por Secretaría.'],
            'bloqueado' => ['bloqueado', 'catequista', 'Tu cuenta fue bloqueada. Contacta a Secretaría.'],
            'rol no válido' => ['aprobado', 'usuario', 'Tu cuenta no tiene un estado o rol válido. Contacta a Secretaría.'],
        ];
    }

    public function test_throttle_applies_equally_to_unknown_and_existing_emails(): void
    {
        $user = User::factory()->create(['role' => 'catequista', 'status' => 'aprobado']);

        foreach (['nadie@example.com', $user->email] as $email) {
            for ($i = 0; $i < 5; $i++) {
                $this->post('/login', ['email' => $email, 'password' => 'incorrecta']);
            }
            $this->assertSame(5, RateLimiter::attempts($this->throttleKey($email)));

            $this->from('/login')->post('/login', ['email' => $email, 'password' => 'incorrecta']);
            $this->assertStringStartsWith('Demasiados intentos de inicio de sesión.', session('errors')->first('email'));
            $this->flushSession();
        }

        // Mientras dure el límite, ni la contraseña correcta permite entrar.
        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertStringStartsWith('Demasiados intentos de inicio de sesión.', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_successful_login_resets_the_attempt_counter(): void
    {
        $user = User::factory()->create(['role' => 'catequista', 'status' => 'aprobado']);
        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta']);
        }
        $this->assertSame(4, RateLimiter::attempts($this->throttleKey($user->email)));

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, RateLimiter::attempts($this->throttleKey($user->email)));
    }

    /** Lo que ve quien intenta entrar: código, destino, errores y qué campos se conservan. */
    private function failedLogin(string $email, string $password): array
    {
        $response = $this->from('/login')->post('/login', ['email' => $email, 'password' => $password]);

        return [
            'status' => $response->getStatusCode(),
            'location' => $response->headers->get('Location'),
            'errors' => session('errors')->getBag('default')->toArray(),
            'old_input' => array_keys(session()->getOldInput()),
        ];
    }

    private function throttleKey(string $email): string
    {
        return Str::transliterate(Str::lower($email).'|127.0.0.1');
    }
}
