<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Support\AuthTestCase;

class PasswordUpdateTest extends AuthTestCase
{
    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create(['role' => 'catequista', 'status' => 'aprobado']);

        $response = $this
            ->actingAs($user)
            ->from(route('cuenta.password.edit'))
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cuenta.password.edit'));

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create(['role' => 'catequista', 'status' => 'aprobado']);

        $response = $this
            ->actingAs($user)
            ->from(route('cuenta.password.edit'))
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrors('current_password')
            ->assertRedirect(route('cuenta.password.edit'));

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_guest_cannot_use_authenticated_password_update(): void
    {
        $this->put('/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
