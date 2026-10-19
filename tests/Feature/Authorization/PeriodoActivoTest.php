<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole as R;
use App\Models\Secretaria\Periodo;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\CatequesisWebTestCase;

/** Periodo activo de la sesión: elección, valor por defecto, periodos eliminados y nombre único. */
class PeriodoActivoTest extends CatequesisWebTestCase
{
    private const NOTICE = 'El periodo que tenías seleccionado ya no está disponible. Ahora trabajas en ';

    private function login(R $role = R::Catequista): \App\Services\Authorization\AccessContext
    {
        $context = $this->context($role);
        $this->actingAs(User::findOrFail($context->userId));

        return $context;
    }

    private function period(int $id, string $start, string $end, int $estado = 1): void
    {
        DB::table('periodos')->where('id', $id)->update(['fecha_inicio' => $start, 'fecha_fin' => $end, 'estado' => $estado]);
    }

    private function softDelete(int $id): void
    {
        DB::table('periodos')->where('id', $id)->update(['deleted_at' => now()]);
    }

    /** El layout imprime el aviso con @json (flags 15). */
    private function noticeFor(string $name): string
    {
        return json_encode(self::NOTICE.$name.'.', 15);
    }

    public function test_1_choosing_a_deleted_period_is_rejected_and_session_is_kept(): void
    {
        $this->login(R::Secretaria);
        $this->softDelete(2);

        $this->from('/secretaria/grupos')->post('/periodo-activo/cambiar', ['periodo_id' => 2])
            ->assertRedirect('/secretaria/grupos')
            ->assertSessionHasErrors(['periodo_id' => 'El periodo seleccionado no existe o fue eliminado.']);
        $this->assertSame(1, session('periodo_activo_id'));
    }

    public function test_2_choosing_a_valid_period_stores_readable_name_and_message(): void
    {
        $this->login(R::Secretaria);
        $this->period(2, '2025-02-01', '2025-11-30', estado: 0);

        $this->from('/secretaria/grupos')->post('/periodo-activo/cambiar', ['periodo_id' => 2])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Periodo cambiado correctamente a: Febrero 2025 - Noviembre 2025.');
        $this->assertSame(2, session('periodo_activo_id'), 'Los periodos inactivos se pueden elegir.');
        $this->assertSame('Febrero 2025 - Noviembre 2025', session('periodo_activo_nombre'));
    }

    public static function rolePages(): array
    {
        return ['catequista' => [R::Catequista, '/catequista/mi-grupo'], 'secretaria' => [R::Secretaria, '/secretaria/grupos']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('rolePages')]
    public function test_3_deleted_session_period_is_replaced_by_default_with_notice(R $role, string $page): void
    {
        $this->login($role);
        $this->period(1, '2026-01-01', '2026-12-31');
        $this->period(2, '2025-01-01', '2025-12-31');
        $this->withSession(['periodo_activo_id' => 2]);
        $this->softDelete(2);

        $this->get($page)->assertOk()->assertSee($this->noticeFor('Enero 2026 - Diciembre 2026'), false);
        $this->assertSame(1, session('periodo_activo_id'));
        $this->assertSame('Enero 2026 - Diciembre 2026', session('periodo_activo_nombre'));
    }

    public function test_4_secretary_never_saves_new_records_with_a_deleted_period(): void
    {
        $this->login(R::Secretaria);
        $this->period(1, '2026-01-01', '2026-12-31');
        $this->period(2, '2027-01-01', '2027-12-31');
        $this->withSession(['periodo_activo_id' => 2]);
        $this->softDelete(2);

        $this->from('/secretaria/grupos')->post('/secretaria/grupos', ['nombre' => 'Grupo nuevo'])
            ->assertSessionHasNoErrors()->assertRedirect(route('secretaria.grupos.index'));

        $this->assertSame(1, (int) DB::table('grupos')->where('nombre', 'Grupo nuevo')->value('periodo_id'));
        foreach (['grupos', 'inscripciones', 'asigna_grupo', 'evaluaciones'] as $table) {
            $this->assertSame(0, DB::table($table)->where('periodo_id', 2)->count(), "$table no debe usar el periodo eliminado");
        }
    }

    public function test_5_default_prefers_most_recent_active_period_then_most_recent(): void
    {
        $this->login();
        $this->period(1, '2025-01-01', '2025-12-31', estado: 1);
        $this->period(2, '2026-01-01', '2026-12-31', estado: 0);
        $this->app['session.store']->forget(['periodo_activo_id', 'periodo_activo_nombre']);

        $this->get('/catequista/mi-grupo')->assertOk()->assertDontSee(json_encode(self::NOTICE, 15), false);
        $this->assertSame(1, session('periodo_activo_id'), 'El activo más reciente, aunque haya uno inactivo más nuevo.');

        DB::table('periodos')->update(['estado' => 0]);
        $this->app['session.store']->forget(['periodo_activo_id', 'periodo_activo_nombre']);
        $this->get('/catequista/mi-grupo')->assertOk();
        $this->assertSame(2, session('periodo_activo_id'), 'Sin activos: el más reciente no eliminado.');
    }

    public function test_6_without_any_period_session_is_cleared_and_current_message_remains(): void
    {
        $this->login();
        DB::table('periodos')->delete();

        $this->withSession(['periodo_activo_id' => 9999])->followingRedirects()->get('/catequista/mi-grupo')
            ->assertOk()->assertSee('Selecciona un periodo válido');
        $this->assertFalse(session()->has('periodo_activo_id'));
        $this->assertFalse(session()->has('periodo_activo_nombre'));
    }

    public function test_7_middleware_and_controller_use_the_same_name(): void
    {
        $this->login(R::Secretaria);
        $this->period(2, '2025-03-01', '2025-10-31');

        $this->from('/secretaria/grupos')->post('/periodo-activo/cambiar', ['periodo_id' => 2]);
        $fromController = session('periodo_activo_nombre');
        $this->get('/secretaria/grupos')->assertOk();

        $this->assertSame('Marzo 2025 - Octubre 2025', $fromController);
        $this->assertSame($fromController, session('periodo_activo_nombre'));
        $this->assertSame(Periodo::nombreLegible('2025-03-01', '2025-10-31'), $fromController);
    }
}
