<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Tests\Support\CatequesisWebTestCase;

class AppAlertsTest extends CatequesisWebTestCase
{
    public function test_every_role_receives_one_shared_flash_payload_with_safe_complete_messages(): void
    {
        $message = '</script><script>alert("dato")</script>';
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role->value, 'status' => 'aprobado', 'comunidad_id' => 1]);
            $html = $this->actingAs($user)->withSession(['error' => $message, 'success' => 'Guardado'])
                ->get(route('cuenta.password.edit'))->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, 'id="app-alert-flash"'));
            preg_match('/<script type="application\/json" id="app-alert-flash">(.*?)<\/script>/s', $html, $matches);
            $payload = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame($message, $payload['error']);
            $this->assertSame('Guardado', $payload['success']);
            $this->assertStringNotContainsString($message, $html);
        }
    }
}
