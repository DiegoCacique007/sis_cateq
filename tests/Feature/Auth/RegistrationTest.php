<?php

namespace Tests\Feature\Auth;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AuthTestCase;

class RegistrationTest extends AuthTestCase
{

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    #[DataProvider('operationalRoles')]
    public function test_new_users_can_request_a_canonical_role(string $role): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'requested_role' => $role,
        ]);

        $this->assertGuest();
        $response->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com', 'role' => 'usuario',
            'requested_role' => $role, 'status' => 'pendiente',
        ]);
    }

    public static function operationalRoles(): array
    {
        return [['secretaria'], ['catequista'], ['parroco'], ['coordinador_general'], ['coordinador_comunidades']];
    }

    public function test_registration_rejects_legacy_role_names(): void
    {
        foreach (['coord_general', 'coord_comunidad'] as $role) {
            $this->from('/register')->post('/register', [
                'name' => 'Test User', 'email' => 'test@example.com',
                'password' => 'password', 'password_confirmation' => 'password',
                'requested_role' => $role,
            ])->assertRedirect('/register')->assertSessionHasErrors('requested_role');
        }

        $this->assertDatabaseCount('users', 0);
    }
}
