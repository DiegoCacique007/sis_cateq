<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Support\AuthTestCase;

class PasswordResetTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_reset_password_link_screen_only_requests_email(): void
    {
        $this->get('/forgot-password')->assertOk()
            ->assertSee('name="email"', false)
            ->assertDontSee('name="password"', false)
            ->assertDontSee('name="password_confirmation"', false);
    }

    public function test_reset_link_requires_a_valid_email(): void
    {
        foreach (['', 'not-an-email'] as $email) {
            $this->from('/forgot-password')->post('/forgot-password', ['email' => $email])
                ->assertRedirect('/forgot-password')->assertSessionHasErrors('email');
        }

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_request_sends_a_token_without_changing_password_even_with_extra_password_fields(): void
    {
        $user = User::factory()->create();
        $originalPassword = $user->password;
        $originalRememberToken = $user->remember_token;

        $this->from('/forgot-password')->post('/forgot-password', [
            'email' => $user->email,
            'password' => 'attacker-password',
            'password_confirmation' => 'attacker-password',
        ])->assertRedirect('/forgot-password')->assertSessionHasNoErrors()->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $storedToken = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
            $this->assertNotSame($notification->token, $storedToken);
            $this->assertTrue(Hash::check($notification->token, $storedToken));

            return true;
        });

        $this->assertSame($originalPassword, $user->refresh()->password);
        $this->assertSame($originalRememberToken, $user->remember_token);
        $this->assertGuest();
    }

    public function test_unknown_email_and_throttled_requests_have_the_same_public_response(): void
    {
        $user = User::factory()->create();
        $this->requestToken($user);
        $message = session('status');
        $storedToken = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');

        foreach ([$user->email, 'missing@example.com'] as $email) {
            $this->from('/forgot-password')->post('/forgot-password', ['email' => $email])
                ->assertRedirect('/forgot-password')->assertSessionHasNoErrors()
                ->assertSessionHas('status', $message);
        }

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
        Notification::assertCount(1);
        $this->assertDatabaseCount('password_reset_tokens', 1);
        $this->assertSame($storedToken, DB::table('password_reset_tokens')->where('email', $user->email)->value('token'));
    }

    public function test_reset_password_screen_contains_all_required_fields(): void
    {
        $user = User::factory()->create();
        $token = $this->requestToken($user);
        $notification = Notification::sent($user, ResetPassword::class)->first();

        $this->get($notification->toMail($user)->actionUrl)->assertOk()
            ->assertSee('name="token"', false)->assertSee($token)
            ->assertSee('name="email"', false)->assertSee($user->email)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_valid_token_resets_password_rotates_remember_token_and_is_consumed_without_login(): void
    {
        $user = User::factory()->create();
        $originalRememberToken = $user->remember_token;
        $token = $this->requestToken($user);
        Event::fake([PasswordReset::class]);

        $this->post('/reset-password', $this->resetData($user, $token))
            ->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
        $this->assertFalse(Hash::check('password', $user->password));
        $this->assertNotSame($originalRememberToken, $user->remember_token);
        $this->assertSame(60, strlen($user->remember_token));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event) => $event->user->is($user));
        $this->assertGuest();
    }

    public function test_old_password_fails_and_new_password_allows_login_after_reset(): void
    {
        $user = User::factory()->create(['role' => UserRole::Catequista->value, 'status' => 'aprobado']);
        $this->post('/reset-password', $this->resetData($user, $this->requestToken($user)))
            ->assertSessionHasNoErrors();
        $this->assertGuest();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => $user->email, 'password' => 'new-password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_token_cannot_reset_password(): void
    {
        $user = User::factory()->create();
        $this->requestToken($user);

        $this->post('/reset-password', $this->resetData($user, 'invalid-token'))
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
        $this->assertGuest();
    }

    public function test_token_for_another_email_cannot_reset_either_password(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $token = $this->requestToken($owner);
        $this->requestToken($other);

        $this->post('/reset-password', $this->resetData($other, $token))
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $owner->refresh()->password));
        $this->assertTrue(Hash::check('password', $other->refresh()->password));
        $this->assertDatabaseCount('password_reset_tokens', 2);
        $this->assertGuest();
    }

    public function test_password_confirmation_must_match(): void
    {
        $user = User::factory()->create();
        $data = $this->resetData($user, $this->requestToken($user));
        $data['password_confirmation'] = 'different-password';

        $this->post('/reset-password', $data)->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
        $this->assertDatabaseCount('password_reset_tokens', 1);
        $this->assertGuest();
    }

    public function test_token_is_required(): void
    {
        $user = User::factory()->create();
        $data = $this->resetData($user, '');
        unset($data['token']);

        $this->post('/reset-password', $data)->assertSessionHasErrors('token');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
        $this->assertGuest();
    }

    public function test_expired_token_cannot_reset_password(): void
    {
        $user = User::factory()->create();
        $token = $this->requestToken($user);
        $this->travel(config('auth.passwords.users.expire') + 1)->minutes();

        $this->post('/reset-password', $this->resetData($user, $token))
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
        $this->assertGuest();
        $this->travelBack();
    }

    public function test_used_token_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        $data = $this->resetData($user, $this->requestToken($user));
        $this->post('/reset-password', $data)->assertSessionHasNoErrors();
        $data['password'] = $data['password_confirmation'] = 'another-password';

        $this->post('/reset-password', $data)->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
        $this->assertGuest();
    }

    private function requestToken(User $user): string
    {
        $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect('/forgot-password')->assertSessionHasNoErrors()->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);

        return Notification::sent($user, ResetPassword::class)->last()->token;
    }

    private function resetData(User $user, string $token): array
    {
        return [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];
    }
}
