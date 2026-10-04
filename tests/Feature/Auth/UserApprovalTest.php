<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\AsegurarPeriodoActivo;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AuthTestCase;

class UserApprovalTest extends AuthTestCase
{
    public static function coordinatorRoles(): array
    {
        return [['coordinador_general'], ['coordinador_comunidades']];
    }

    #[DataProvider('coordinatorRoles')]
    public function test_secretaria_can_approve_canonical_coordinator_roles(string $role): void
    {
        $this->withoutMiddleware(AsegurarPeriodoActivo::class);
        $secretaria = User::factory()->create(['role' => 'secretaria', 'status' => 'aprobado']);
        $pending = User::factory()->create(['role' => 'usuario', 'requested_role' => $role]);

        $this->actingAs($secretaria)->post(route('secretaria.usuarios.aprobar', $pending), ['role' => $role])
            ->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $pending->id, 'role' => $role, 'status' => 'aprobado', 'approved_by' => $secretaria->id,
        ]);
        $this->assertNotNull($pending->fresh()->approved_at);
    }

    public function test_approval_rejects_legacy_and_unknown_roles(): void
    {
        $this->withoutMiddleware(AsegurarPeriodoActivo::class);
        $secretaria = User::factory()->create(['role' => 'secretaria', 'status' => 'aprobado']);
        $pending = User::factory()->create(['role' => 'usuario']);

        foreach (['coord_general', 'coord_comunidad', 'admin', 'desconocido'] as $role) {
            $this->actingAs($secretaria)->postJson(route('secretaria.usuarios.aprobar', $pending), ['role' => $role])
                ->assertUnprocessable()->assertJsonValidationErrors('role');
        }

        $this->assertSame('pendiente', $pending->fresh()->status);
        $this->assertSame('usuario', $pending->fresh()->role);
    }
}
