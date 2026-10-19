<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Tests\Support\AuthTestCase;

/** Límite por IP del login (solo intentos fallidos) y de la recuperación de contraseña (todas las solicitudes). */
class IpThrottleTest extends AuthTestCase
{
    private const LOGIN_LIMITED = 'Demasiados intentos de inicio de sesión. Espera unos minutos e inténtalo de nuevo.';

    private const RESET_LIMITED = 'Demasiadas solicitudes de recuperación de contraseña. Espera unos minutos e inténtalo de nuevo.';

    private const RESET_STATUS = 'Si existe una cuenta con ese correo, recibirás un enlace para restablecer tu contraseña. Si ya lo solicitaste, espera un minuto antes de intentarlo de nuevo.';

    protected function setUp(): void
    {
        parent::setUp();

        // Sin la espera fija de 200 ms por intento fallido: aquí se prueban los límites, no los tiempos.
        config(['auth.timebox_duration' => 0]);
        Notification::fake();
    }

    public function test_login_ip_limit_blocks_the_31st_attempt_even_with_correct_password(): void
    {
        $user = $this->approvedUser();
        $this->failLogins(30, '10.0.0.1');

        $this->login($user->email, 'password', '10.0.0.1');

        $this->assertSame(['email' => [self::LOGIN_LIMITED]], session('errors')->getBag('default')->toArray());
        $this->assertGuest();
    }

    public function test_login_ip_limit_does_not_reveal_whether_the_email_exists(): void
    {
        $user = $this->approvedUser();
        $this->failLogins(30, '10.0.0.1');

        $unknown = $this->login('nadie@example.com', 'incorrecta', '10.0.0.1');
        $this->flushSession();
        $existing = $this->login($user->email, 'incorrecta', '10.0.0.1');

        $this->assertSame($unknown, $existing);
        $this->assertSame(['email' => [self::LOGIN_LIMITED]], $unknown['errors']);
        $this->assertSame(['email'], $unknown['old_input']);
    }

    public function test_login_ip_limit_does_not_affect_another_ip(): void
    {
        $user = $this->approvedUser();
        $this->failLogins(30, '10.0.0.1');
        $this->login($user->email, 'password', '10.0.0.1');
        $this->assertSame(self::LOGIN_LIMITED, session('errors')->first('email'));
        $this->flushSession();

        $this->login($user->email, 'password', '10.0.0.2');

        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_login_does_not_reset_the_ip_counter(): void
    {
        $user = $this->approvedUser();
        $this->failLogins(29, '10.0.0.1');
        $this->login($user->email, 'password', '10.0.0.1');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout');

        $this->failLogins(1, '10.0.0.1', 'ultimo');
        $this->login($user->email, 'password', '10.0.0.1');

        $this->assertSame(self::LOGIN_LIMITED, session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_login_ip_limit_expires_after_10_minutes(): void
    {
        $user = $this->approvedUser();
        $this->failLogins(30, '10.0.0.1');
        $this->login($user->email, 'password', '10.0.0.1');
        $this->assertSame(self::LOGIN_LIMITED, session('errors')->first('email'));
        $this->flushSession();

        $this->travel(11)->minutes();
        $this->login($user->email, 'password', '10.0.0.1');

        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_logins_do_not_count_toward_the_ip_limit(): void
    {
        for ($i = 1; $i <= 40; $i++) {
            $user = $this->approvedUser();
            $this->login($user->email, 'password', '10.0.0.1');
            $this->assertAuthenticatedAs($user);
            $this->post('/logout');
        }
    }

    public function test_forgot_password_ip_limit_blocks_the_21st_request_without_sending(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $email = $i % 2 ? $this->approvedUser()->email : "nadie{$i}@example.com";
            $this->forgot($email, '10.0.0.1')->assertSessionHasNoErrors()->assertSessionHas('status', self::RESET_STATUS);
        }
        $user = $this->approvedUser();

        $this->forgot($user->email, '10.0.0.1')
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors(['email' => self::RESET_LIMITED]);

        $this->assertSame(['email' => $user->email], session()->getOldInput());
        Notification::assertNotSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_ip_limit_does_not_reveal_whether_the_email_exists(): void
    {
        $user = $this->approvedUser();
        $this->forgotMany(20, '10.0.0.1');

        $unknown = $this->forgotResponse('nadie@example.com', '10.0.0.1');
        $this->flushSession();
        $existing = $this->forgotResponse($user->email, '10.0.0.1');

        $this->assertSame($unknown, $existing);
        $this->assertSame(['email' => [self::RESET_LIMITED]], $unknown['errors']);
        Notification::assertNotSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_ip_limit_answers_json_with_429(): void
    {
        $this->forgotMany(20, '10.0.0.1');

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->postJson('/forgot-password', ['email' => 'nadie@example.com'])
            ->assertStatus(429)
            ->assertJsonPath('message', self::RESET_LIMITED)
            ->assertJsonPath('errors.email.0', self::RESET_LIMITED);
    }

    public function test_forgot_password_ip_limit_spares_other_ips_and_expires_after_10_minutes(): void
    {
        $this->forgotMany(20, '10.0.0.1');
        $this->forgot('nadie@example.com', '10.0.0.1')->assertSessionHasErrors(['email' => self::RESET_LIMITED]);
        $this->flushSession();

        $this->forgot('nadie@example.com', '10.0.0.2')->assertSessionHasNoErrors()->assertSessionHas('status', self::RESET_STATUS);
        $this->flushSession();

        $this->travel(11)->minutes();
        $this->forgot('nadie@example.com', '10.0.0.1')->assertSessionHasNoErrors()->assertSessionHas('status', self::RESET_STATUS);
    }

    private function approvedUser(): User
    {
        return User::factory()->create(['role' => 'catequista', 'status' => 'aprobado']);
    }

    /** Un fallo por correo distinto: solo cuenta el límite por IP, nunca el de correo|ip. */
    private function failLogins(int $count, string $ip, string $prefix = 'fallo'): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->login("{$prefix}{$i}@example.com", 'incorrecta', $ip);
        }
        $this->flushSession();
    }

    /** Lo que ve quien intenta entrar: código, destino, errores y qué campos se conservan. */
    private function login(string $email, string $password, string $ip): array
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from('/login')->post('/login', ['email' => $email, 'password' => $password]);

        return [
            'status' => $response->getStatusCode(),
            'location' => $response->headers->get('Location'),
            'errors' => session('errors')?->getBag('default')->toArray(),
            'old_input' => array_keys(session()->getOldInput()),
        ];
    }

    private function forgot(string $email, string $ip): \Illuminate\Testing\TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from('/forgot-password')->post('/forgot-password', ['email' => $email]);
    }

    private function forgotMany(int $count, string $ip): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->forgot("solicitud{$i}@example.com", $ip);
        }
        $this->flushSession();
    }

    /** Lo que ve quien pide el enlace: código, destino, errores, aviso y campos conservados. */
    private function forgotResponse(string $email, string $ip): array
    {
        $response = $this->forgot($email, $ip);

        return [
            'status' => $response->getStatusCode(),
            'location' => $response->headers->get('Location'),
            'errors' => session('errors')?->getBag('default')->toArray(),
            'notice' => session('status'),
            'old_input' => array_keys(session()->getOldInput()),
        ];
    }
}
