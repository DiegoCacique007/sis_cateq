<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Tests\Support\AuthTestCase;

class AuthenticationTest extends AuthTestCase
{

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create(['role' => 'catequista', 'status' => 'aprobado']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    #[DataProviderExternal(OperationalAccessTest::class, 'deniedAccounts')]
    public function test_login_rejects_unapproved_or_invalid_accounts(string $status, string $role): void
    {
        $user = User::factory()->create(['role' => $role, 'status' => $status]);

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
