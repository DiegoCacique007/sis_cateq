<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Catequista\CatequistaController;
use App\Http\Controllers\CoordComunidad\CoordComunidadController;
use App\Http\Controllers\CoordGeneral\CoordGeneralController;
use App\Http\Controllers\Parroco\ParrocoController;
use App\Http\Controllers\Secretaria\DashboardController;
use App\Http\Middleware\AsegurarPeriodoActivo;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AuthTestCase;

class OperationalAccessTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Se prueba la autorización real de las rutas sin consultar tablas académicas.
        $this->withoutMiddleware(AsegurarPeriodoActivo::class);
    }

    public static function operationalRoles(): array
    {
        return [
            ['secretaria', 'secretaria.dashboard', DashboardController::class],
            ['catequista', 'catequista.dashboard', CatequistaController::class],
            ['parroco', 'parroco.dashboard', ParrocoController::class],
            ['coordinador_general', 'coordinador_general.dashboard', CoordGeneralController::class],
            ['coordinador_comunidades', 'coordinador_comunidades.dashboard', CoordComunidadController::class],
        ];
    }

    #[DataProvider('operationalRoles')]
    public function test_approved_users_can_access_their_role_dashboard(string $role, string $route, string $controller): void
    {
        $user = User::factory()->create(['role' => $role, 'status' => 'aprobado']);
        $this->mock($controller, function (MockInterface $mock) {
            $mock->shouldReceive('index')->once()->andReturn(response('allowed'));
        });

        $this->actingAs($user)->get(route($route))->assertOk()->assertSee('allowed');
    }

    public static function deniedAccounts(): array
    {
        return [
            'pendiente' => ['pendiente', 'secretaria'],
            'bloqueado' => ['bloqueado', 'secretaria'],
            'estado desconocido' => ['otro', 'secretaria'],
            'rol desconocido' => ['aprobado', 'desconocido'],
            'usuario no operativo' => ['aprobado', 'usuario'],
            'alias general antiguo' => ['aprobado', 'coord_general'],
            'alias comunidad antiguo' => ['aprobado', 'coord_comunidad'],
            'admin no operativo' => ['aprobado', 'admin'],
        ];
    }

    #[DataProvider('deniedAccounts')]
    public function test_unapproved_or_invalid_accounts_are_denied_and_logged_out(string $status, string $role): void
    {
        $user = User::factory()->create(['role' => $role, 'status' => $status]);

        $this->actingAs($user)->withSession(['periodo_activo_id' => 999])
            ->get(route('secretaria.dashboard'))
            ->assertForbidden()->assertSessionMissing('periodo_activo_id');

        $this->assertGuest();
    }

    public function test_approved_user_with_wrong_role_gets_403_without_losing_session(): void
    {
        $user = User::factory()->create(['role' => 'catequista', 'status' => 'aprobado']);

        $this->actingAs($user)->get(route('secretaria.dashboard'))->assertForbidden();
        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_cannot_access_an_operational_route(): void
    {
        $this->get(route('secretaria.dashboard'))->assertRedirect(route('login'));
    }

    public function test_blocking_after_login_is_enforced_on_the_next_request(): void
    {
        $user = User::factory()->create(['role' => 'secretaria', 'status' => 'aprobado']);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticated();
        $sessionId = session()->getId();
        $token = session()->token();

        User::whereKey($user->id)->update(['status' => 'bloqueado']);
        // Un nuevo request real reconstruye el guard y carga al usuario desde la BD.
        Auth::forgetGuards();

        $this->get(route('secretaria.dashboard'))->assertForbidden();

        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame($token, session()->token());
    }

    public function test_shared_operational_endpoints_require_approval(): void
    {
        $user = User::factory()->create(['role' => 'secretaria', 'status' => 'bloqueado']);

        $this->actingAs($user)->get('/dashboard')->assertForbidden();
        $this->actingAs($user)->post('/periodo-activo/cambiar', ['periodo_id' => 1])->assertForbidden();
    }

    public function test_all_operational_routes_declare_the_required_middleware_in_order(): void
    {
        $count = 0;

        foreach (Route::getRoutes() as $route) {
            foreach (UserRole::cases() as $role) {
                if (str_starts_with($route->getName() ?? '', $role->value.'.')) {
                    $this->assertSame([
                        'web', 'auth', 'approved', 'role:'.$role->value,
                        \App\Http\Middleware\NoCacheHeaders::class, 'periodo.activo',
                    ], $route->gatherMiddleware(), $route->getName());
                    $count++;
                }
            }
        }

        $this->assertSame(81, $count);
    }

    public function test_check_role_preserves_pipe_separated_roles(): void
    {
        Route::middleware(['web', 'auth', 'approved', 'role:secretaria|coordinador_general'])
            ->get('/test-role-pipe', fn () => response('allowed'));
        $user = User::factory()->create(['role' => 'coordinador_general', 'status' => 'aprobado']);

        $this->actingAs($user)->get('/test-role-pipe')->assertOk();
    }

    public function test_check_role_rejects_unknown_roles_even_if_configured_on_a_route(): void
    {
        Route::middleware(['web', 'auth', 'role:desconocido'])
            ->get('/test-invalid-role', fn () => response('allowed'));
        $user = User::factory()->create(['role' => 'desconocido', 'status' => 'aprobado']);

        $this->actingAs($user)->get('/test-invalid-role')->assertForbidden();
    }
}
