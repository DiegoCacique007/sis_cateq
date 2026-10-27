<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole;
use App\Models\User;
use Tests\Support\CatequesisWebTestCase;

class GestionUsuariosTest extends CatequesisWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/2026_10_19_000001_create_user_status_changes_table.php'))->up();
        $context = $this->context(UserRole::Secretaria);
        $this->actingAs(User::findOrFail($context->userId));
    }

    public function test_sections_counts_and_access_are_consistent(): void
    {
        $pending = User::factory()->create(['role' => 'usuario', 'status' => 'pendiente']);
        $active = User::factory()->create(['role' => 'catequista', 'status' => 'aprobado']);
        $blocked = User::factory()->create(['role' => 'catequista', 'status' => 'bloqueado']);
        $invalid = User::factory()->create(['role' => 'desconocido', 'status' => 'aprobado']);
        $response = $this->get(route('secretaria.usuarios.index'))->assertOk();
        $response->assertViewHas('resumen', ['todos' => 5, 'pendientes' => 1, 'activos' => 2, 'inactivos' => 1]);
        foreach (['pendientes' => [$pending->id], 'activos' => [auth()->id(), $active->id], 'inactivos' => [$blocked->id]] as $section => $ids) {
            $list = $this->get(route('secretaria.usuarios.index', ['seccion' => $section]))->assertOk()->viewData('usuarios');
            $this->assertEqualsCanonicalizing($ids, $list->pluck('id')->all());
            $this->assertNotContains($invalid->id, $list->pluck('id')->all());
        }
        $this->assertSame(5, $response->viewData('usuarios')->total());
    }

    public function test_legacy_pending_url_uses_same_view_and_forces_pending_section(): void
    {
        $pending = User::factory()->create(['status' => 'pendiente', 'requested_role' => 'coordinador_comunidades']);
        $this->get(route('secretaria.usuarios.pendientes', ['seccion' => 'activos', 'estado' => 'aprobado']))
            ->assertOk()->assertViewIs('secretaria.usuarios.index')->assertViewHas('seccion', 'pendientes')
            ->assertViewHas('usuarios', fn ($users) => $users->pluck('id')->all() === [$pending->id])
            ->assertSee('name="comunidad_id"', false);
    }

    public function test_search_role_and_pagination_remain_in_selected_section(): void
    {
        User::factory()->count(12)->create(['name' => 'Cuenta visible', 'status' => 'bloqueado', 'role' => 'catequista']);
        User::factory()->create(['name' => 'Cuenta visible', 'status' => 'aprobado', 'role' => 'catequista']);
        User::factory()->create(['name' => 'Cuenta visible', 'status' => 'bloqueado', 'role' => 'parroco']);
        $response = $this->get(route('secretaria.usuarios.index', ['seccion' => 'inactivos', 'buscar' => 'Cuenta visible', 'rol' => 'catequista', 'per_page' => 10]))->assertOk();
        $users = $response->viewData('usuarios');
        $this->assertSame(12, $users->total());
        $this->assertCount(10, $users);
        $this->assertStringContainsString('seccion=inactivos', $users->nextPageUrl());
        $this->assertStringContainsString('rol=catequista', $users->nextPageUrl());
    }

    public function test_approval_rejection_and_reactivation_keep_existing_transitions(): void
    {
        $pending = User::factory()->create(['role' => 'usuario', 'status' => 'pendiente', 'requested_role' => 'catequista']);
        $url = route('secretaria.usuarios.index', ['seccion' => 'pendientes']);
        $this->from($url)->post(route('secretaria.usuarios.bloquear', $pending))->assertRedirect($url);
        $this->assertSame('bloqueado', $pending->fresh()->status);
        $this->post(route('secretaria.usuarios.reactivar', $pending))->assertSessionHasNoErrors();
        $this->assertSame('pendiente', $pending->fresh()->status);
        $this->post(route('secretaria.usuarios.aprobar', $pending), ['role' => 'catequista'])->assertSessionHasNoErrors();
        $this->assertTrue($pending->fresh()->isApprovedForAccess());
        $this->post(route('secretaria.usuarios.bloquear', $pending));
        $this->post(route('secretaria.usuarios.reactivar', $pending));
        $this->assertTrue($pending->fresh()->isApprovedForAccess());
    }

    public function test_navigation_is_unified_and_dashboard_targets_pending_section(): void
    {
        $html = $this->get(route('secretaria.usuarios.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('<span>Cuentas de usuario</span>', $html);
        $this->assertStringNotContainsString('<span>Usuarios pendientes</span>', $html);
        $this->assertStringContainsString('<span>Gestión de usuarios</span>', $html);
        $dashboard = file_get_contents(resource_path('views/secretaria/dashboard.blade.php'));
        $this->assertStringContainsString("route('secretaria.usuarios.index', ['seccion' => 'pendientes'])", $dashboard);
    }
}
