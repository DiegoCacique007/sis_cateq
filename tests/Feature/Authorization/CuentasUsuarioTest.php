<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole as R;
use App\Http\Middleware\EnsureUserApproved;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\Support\CatequesisWebTestCase;

/** «Cuentas de usuario»: lista, filtros y acciones de Secretaría con sus protecciones y su historial. */
class CuentasUsuarioTest extends CatequesisWebTestCase
{
    private const MALICIOUS_NAME = "Ana');alert(1);//\"<script>alert(2)</script>";

    protected function setUp(): void
    {
        parent::setUp();

        (require database_path('migrations/2026_10_19_000001_create_user_status_changes_table.php'))->up();
    }

    public function test_secretaria_sees_all_accounts_and_other_roles_cannot(): void
    {
        $secretaria = $this->secretaria();
        foreach (['catequista', 'parroco', 'coordinador_general', 'coordinador_comunidades'] as $role) {
            $this->account(['name' => "Cuenta {$role}", 'role' => $role]);
        }
        $this->account(['name' => 'Solicitud pendiente', 'role' => 'usuario', 'status' => 'pendiente']);
        $this->account(['name' => 'Cuenta bloqueada', 'status' => 'bloqueado']);

        $this->actingAs($secretaria)->get(route('secretaria.usuarios.index'))
            ->assertOk()
            ->assertSee('Gestión de usuarios')
            ->assertSeeInOrder(['Cuenta bloqueada', 'Cuenta catequista', 'Cuenta coordinador_comunidades', 'Cuenta coordinador_general', 'Cuenta parroco', 'Solicitud pendiente'])
            ->assertSee(['Pendiente', 'Aprobada', 'Bloqueada'])
            ->assertSee(['Secretaría', 'Catequista', 'Párroco', 'Coordinador General', 'Coordinador de Comunidades', 'Sin rol asignado'])
            ->assertSee('href="'.route('secretaria.usuarios.index').'"', false);

        foreach (['catequista', 'parroco', 'coordinador_general', 'coordinador_comunidades'] as $role) {
            $this->actingAs(User::where('role', $role)->firstOrFail())
                ->get(route('secretaria.usuarios.index'))->assertForbidden();
        }
        auth()->logout();
        $this->get(route('secretaria.usuarios.index'))->assertRedirect(route('login'));
    }

    public function test_filters_search_and_pagination_keep_the_query(): void
    {
        $secretaria = $this->secretaria();
        $this->account(['name' => 'Ana Bloqueada', 'status' => 'bloqueado']);
        $this->account(['name' => 'Beto Parroco', 'role' => 'parroco']);
        $this->account(['name' => 'Carla Pendiente', 'email' => 'carla@example.org', 'role' => 'usuario', 'status' => 'pendiente']);
        $this->actingAs($secretaria);

        $this->get(route('secretaria.usuarios.index', ['estado' => 'bloqueado']))
            ->assertSee('Ana Bloqueada')->assertDontSee('Beto Parroco')->assertDontSee('Carla Pendiente');
        $this->get(route('secretaria.usuarios.index', ['rol' => 'parroco']))
            ->assertSee('Beto Parroco')->assertDontSee('Ana Bloqueada');
        $this->get(route('secretaria.usuarios.index', ['buscar' => 'carla@example']))
            ->assertSee('Carla Pendiente')->assertDontSee('Ana Bloqueada');
        $this->get(route('secretaria.usuarios.index', ['buscar' => 'Beto']))
            ->assertSee('Beto Parroco')->assertDontSee('Carla Pendiente');

        foreach (range(1, 12) as $i) {
            $this->account(['name' => sprintf('Paginada %02d', $i)]);
        }
        $this->get(route('secretaria.usuarios.index', ['estado' => 'aprobado', 'per_page' => 10]))
            ->assertOk()
            ->assertSee('page=2', false)
            ->assertSee('estado=aprobado', false)
            ->assertSee('per_page=10', false);
    }

    public function test_user_names_never_reach_javascript_in_the_list(): void
    {
        $this->account(['name' => self::MALICIOUS_NAME]);
        $this->account(['name' => self::MALICIOUS_NAME, 'email' => 'bloqueada@example.org', 'status' => 'bloqueado']);
        $this->account(['name' => self::MALICIOUS_NAME, 'email' => 'coord@example.org', 'role' => 'coordinador_comunidades', 'comunidad_id' => 1]);

        $html = $this->actingAs($this->secretaria())->get(route('secretaria.usuarios.index'))->assertOk()->getContent();

        preg_match_all('/\son[a-z]+\s*=\s*"([^"]*)"/i', $html, $handlers);
        foreach ($handlers[1] as $handler) {
            $this->assertStringNotContainsString('alert(', html_entity_decode($handler, ENT_QUOTES | ENT_HTML5));
        }
        preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $html, $scripts);
        foreach ($scripts[1] as $script) {
            $this->assertStringNotContainsString('alert(1)', $script);
            $this->assertStringNotContainsString('alert(2)', $script);
        }
        $this->assertStringContainsString(e(self::MALICIOUS_NAME), $html);
        $this->assertStringNotContainsString('<script>alert(2)</script>', $html);
    }

    public function test_blocking_a_catequista_keeps_assignments_and_evaluations(): void
    {
        $context = $this->context(R::Catequista);
        $ids = $this->records($context);
        $before = [DB::table('asigna_grupo')->where('catequista_id', $context->userId)->count(), DB::table('evaluaciones')->count()];

        $this->actingAs($this->secretaria())->from(route('secretaria.usuarios.index'))
            ->post(route('secretaria.usuarios.bloquear', $context->userId))
            ->assertRedirect(route('secretaria.usuarios.index'))
            ->assertSessionHas('status', 'La cuenta de '.User::findOrFail($context->userId)->name.' quedó bloqueada.');

        $this->assertSame('bloqueado', User::findOrFail($context->userId)->status);
        $this->assertSame($before, [DB::table('asigna_grupo')->where('catequista_id', $context->userId)->count(), DB::table('evaluaciones')->count()]);
        $this->assertDatabaseHas('asigna_grupo', ['id' => $ids['assignment'], 'catequista_id' => $context->userId, 'deleted_at' => null]);
        $this->assertDatabaseHas('evaluaciones', ['id' => $ids['evaluation'], 'deleted_at' => null]);
    }

    public function test_block_confirmation_warns_about_groups_in_the_current_period(): void
    {
        $three = $this->catequistaWithGroups(3);
        $one = $this->catequistaWithGroups(1);

        $this->actingAs($this->secretaria())->get(route('secretaria.usuarios.index'))
            ->assertOk()
            ->assertSee("{$three->name} no podrá entrar al sistema. Sus datos se conservarán. Este catequista tiene 3 grupos asignados en el periodo actual.")
            ->assertSee('Para que otro catequista atienda esos grupos, reasígnalos en Asignación de grupos.')
            ->assertSee("{$one->name} no podrá entrar al sistema. Sus datos se conservarán. Este catequista tiene 1 grupo asignado en el periodo actual.");
    }

    public function test_reactivate_restores_access_or_returns_to_pending(): void
    {
        $secretaria = $this->secretaria();
        $catequista = $this->account(['status' => 'bloqueado', 'approved_at' => null]);
        $rechazada = $this->account(['role' => 'usuario', 'status' => 'bloqueado']);
        $activa = $this->account();
        $this->actingAs($secretaria)->from(route('secretaria.usuarios.index'));

        $this->post(route('secretaria.usuarios.reactivar', $catequista))
            ->assertSessionHas('status', "La cuenta de {$catequista->name} quedó reactivada.");
        $this->assertSame(['aprobado', $secretaria->id], [$catequista->fresh()->status, (int) $catequista->fresh()->approved_by]);
        $this->assertNotNull($catequista->fresh()->approved_at);

        $this->post(route('secretaria.usuarios.reactivar', $rechazada))
            ->assertSessionHas('status', "La solicitud de {$rechazada->name} volvió a Usuarios pendientes.");
        $this->assertSame('pendiente', $rechazada->fresh()->status);

        $this->post(route('secretaria.usuarios.reactivar', $activa))
            ->assertSessionHas('error', 'Solo se pueden reactivar cuentas bloqueadas.');
        $this->assertSame('aprobado', $activa->fresh()->status);
    }

    public function test_change_community_of_a_community_coordinator(): void
    {
        $coordinator = $this->account(['role' => 'coordinador_comunidades', 'comunidad_id' => 1]);
        $catequista = $this->account();
        $this->actingAs($this->secretaria())->from(route('secretaria.usuarios.index'));

        $this->post(route('secretaria.usuarios.comunidad', $coordinator), ['comunidad_id' => 2])
            ->assertSessionHas('status', "La comunidad de {$coordinator->name} se actualizó.");
        $this->assertSame(2, (int) $coordinator->fresh()->comunidad_id);

        $this->post(route('secretaria.usuarios.comunidad', $catequista), ['comunidad_id' => 2])
            ->assertSessionHas('error', 'Solo el Coordinador de Comunidades tiene comunidad asignada.');
        $this->assertNull($catequista->fresh()->comunidad_id);

        DB::table('comunidades')->where('id', 1)->update(['deleted_at' => now()]);
        $this->post(route('secretaria.usuarios.comunidad', $coordinator), ['comunidad_id' => 1])
            ->assertSessionHasErrors(['comunidad_id' => 'La opción seleccionada en el campo comunidad no es válida.']);
        $this->assertSame(2, (int) $coordinator->fresh()->comunidad_id);
    }

    public function test_change_role_of_an_approved_account(): void
    {
        $catequista = $this->account();
        $coordinator = $this->account(['role' => 'coordinador_comunidades', 'comunidad_id' => 1]);
        $this->actingAs($this->secretaria())->from(route('secretaria.usuarios.index'));

        $this->post(route('secretaria.usuarios.rol', $catequista), ['role' => 'parroco'])
            ->assertSessionHas('status', "El rol de {$catequista->name} ahora es Párroco.");
        $this->assertSame('parroco', $catequista->fresh()->role);

        $this->post(route('secretaria.usuarios.rol', $catequista), ['role' => 'coordinador_comunidades'])
            ->assertSessionHasErrors(['comunidad_id' => 'Selecciona la comunidad que coordinará.']);
        $this->post(route('secretaria.usuarios.rol', $catequista), ['role' => 'coordinador_comunidades', 'comunidad_id' => 2])
            ->assertSessionHasNoErrors();
        $this->assertSame(['coordinador_comunidades', 2], [$catequista->fresh()->role, (int) $catequista->fresh()->comunidad_id]);

        // Al dejar de ser Coordinador de Comunidades, la comunidad se quita de la cuenta.
        $this->post(route('secretaria.usuarios.rol', $coordinator), ['role' => 'catequista'])->assertSessionHas('status');
        $this->assertSame(['catequista', null], [$coordinator->fresh()->role, $coordinator->fresh()->comunidad_id]);
    }

    public function test_change_role_limits(): void
    {
        $secretaria = $this->secretaria();
        $busy = $this->catequistaWithGroups(3);
        $pending = $this->account(['role' => 'usuario', 'status' => 'pendiente']);
        $this->actingAs($secretaria)->from(route('secretaria.usuarios.index'));

        $this->post(route('secretaria.usuarios.rol', $secretaria), ['role' => 'catequista'])
            ->assertSessionHas('error', 'No puedes cambiar tu propio rol.');
        $this->post(route('secretaria.usuarios.rol', $busy), ['role' => 'parroco'])
            ->assertSessionHas('error', 'Este catequista tiene 3 grupos asignados en el periodo actual. Reasígnalos antes de cambiar su rol.');
        $this->post(route('secretaria.usuarios.rol', $pending), ['role' => 'parroco'])
            ->assertSessionHas('error', 'Solo se puede cambiar el rol de cuentas aprobadas.');

        $this->assertSame(['secretaria', 'catequista', 'usuario'], [$secretaria->fresh()->role, $busy->fresh()->role, $pending->fresh()->role]);
    }

    public function test_last_approved_secretaria_keeps_her_role(): void
    {
        // Simula la carrera: B acaba de bloquear a A y, en la petición de A, A intenta quitarle el rol a B.
        $this->withoutMiddleware(EnsureUserApproved::class);
        $a = $this->secretaria();
        $b = $this->secretaria();
        $a->forceFill(['status' => 'bloqueado'])->save();

        $this->actingAs($a)->from(route('secretaria.usuarios.index'))
            ->post(route('secretaria.usuarios.rol', $b), ['role' => 'catequista'])
            ->assertSessionHas('error', 'No se puede cambiar: debe quedar al menos una cuenta de Secretaría aprobada.');

        $this->assertSame('secretaria', $b->fresh()->role);
    }

    public function test_each_action_writes_a_log_line(): void
    {
        Log::spy();
        [$secretaria, $targets] = $this->runEveryAction();

        foreach ($targets as $action => $user) {
            Log::shouldHaveReceived('info')->with('cuentas.cambio', Mockery::on(
                fn (array $change) => $change['action'] === $action && $change['user_id'] === $user->id && $change['changed_by'] === $secretaria->id
            ))->once();
        }
    }

    public function test_each_action_writes_its_row_in_user_status_changes(): void
    {
        [$secretaria, $targets] = $this->runEveryAction();

        $this->assertDatabaseCount('user_status_changes', 4);
        $this->assertDatabaseHas('user_status_changes', [
            'user_id' => $targets['bloquear']->id, 'changed_by' => $secretaria->id, 'action' => 'bloquear',
            'from_status' => 'aprobado', 'to_status' => 'bloqueado', 'from_role' => 'catequista', 'to_role' => 'catequista',
        ]);
        $this->assertDatabaseHas('user_status_changes', [
            'user_id' => $targets['reactivar']->id, 'changed_by' => $secretaria->id, 'action' => 'reactivar',
            'from_status' => 'bloqueado', 'to_status' => 'aprobado',
        ]);
        $this->assertDatabaseHas('user_status_changes', [
            'user_id' => $targets['cambiar_comunidad']->id, 'changed_by' => $secretaria->id, 'action' => 'cambiar_comunidad',
            'from_comunidad_id' => 1, 'to_comunidad_id' => 2,
        ]);
        $this->assertDatabaseHas('user_status_changes', [
            'user_id' => $targets['cambiar_rol']->id, 'changed_by' => $secretaria->id, 'action' => 'cambiar_rol',
            'from_role' => 'catequista', 'to_role' => 'parroco', 'from_status' => 'aprobado', 'to_status' => 'aprobado',
        ]);
        $this->assertSame(4, DB::table('user_status_changes')->whereNotNull('created_at')->count());
    }

    /** Ejecuta las cuatro acciones con éxito; los rechazos no escriben historial. */
    private function runEveryAction(): array
    {
        $secretaria = $this->secretaria();
        $targets = [
            'bloquear' => $this->account(),
            'reactivar' => $this->account(['status' => 'bloqueado']),
            'cambiar_comunidad' => $this->account(['role' => 'coordinador_comunidades', 'comunidad_id' => 1]),
            'cambiar_rol' => $this->account(),
        ];
        $this->actingAs($secretaria)->from(route('secretaria.usuarios.index'));

        $this->post(route('secretaria.usuarios.bloquear', $targets['bloquear']))->assertSessionHas('status');
        $this->post(route('secretaria.usuarios.reactivar', $targets['reactivar']))->assertSessionHas('status');
        $this->post(route('secretaria.usuarios.comunidad', $targets['cambiar_comunidad']), ['comunidad_id' => 2])->assertSessionHas('status');
        $this->post(route('secretaria.usuarios.rol', $targets['cambiar_rol']), ['role' => 'parroco'])->assertSessionHas('status');
        // Un rechazo no deja fila.
        $this->post(route('secretaria.usuarios.bloquear', $secretaria))->assertSessionHas('error');

        return [$secretaria, $targets];
    }

    private function secretaria(): User
    {
        return User::factory()->create(['role' => 'secretaria', 'status' => 'aprobado']);
    }

    private function account(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'catequista', 'status' => 'aprobado', 'approved_at' => now()], $attributes));
    }

    /** Catequista con $count asignaciones en el periodo activo (1) y una más en otro periodo, que no cuenta. */
    private function catequistaWithGroups(int $count): User
    {
        $catequista = $this->account();
        foreach ([...array_fill(0, $count, 1), 2] as $period) {
            DB::table('asigna_grupo')->insert([
                'catequista_id' => $catequista->id, 'comunidad_id' => 1, 'grupo_id' => null, 'nivel_id' => 1, 'periodo_id' => $period,
            ]);
        }

        return $catequista;
    }
}
