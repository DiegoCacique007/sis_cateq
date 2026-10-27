<?php

namespace App\Http\Controllers\Secretaria;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Secretaria\Comunidad;
use App\Models\User;
use App\Services\Accounts\AccountActionRejected;
use App\Services\Accounts\AccountManager;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Gestión unificada de cuentas y solicitudes de acceso. */
class UsuariosController extends Controller
{
    private const ESTADOS = ['pendiente', 'aprobado', 'bloqueado'];

    public function index(Request $request)
    {
        abort_unless($request->user()->operationalRole() === UserRole::Secretaria, 403);

        $operativos = array_map(fn (UserRole $role) => $role->value, UserRole::cases());
        $roles = [...$operativos, 'usuario'];
        $seccion = in_array($request->query('seccion'), ['todos', 'pendientes', 'activos', 'inactivos'], true) ? $request->query('seccion') : 'todos';
        $estado = in_array($request->query('estado'), self::ESTADOS, true) ? $request->query('estado') : '';
        if ($seccion !== 'todos') {
            $estado = '';
        }
        $rol = in_array($request->query('rol'), $roles, true) ? $request->query('rol') : '';
        $buscar = is_string($request->query('buscar')) ? mb_substr(trim($request->query('buscar')), 0, 100) : '';
        $perPage = (int) $request->query('per_page', 25);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }
        $periodoId = session('periodo_activo_id');
        $resumen = [
            'todos' => User::count(),
            'pendientes' => User::where('status', 'pendiente')->count(),
            'activos' => User::where('status', 'aprobado')->whereIn('role', $operativos)->count(),
            'inactivos' => User::where('status', 'bloqueado')->count(),
        ];

        $usuarios = User::query()
            ->with('comunidad')
            ->withCount(['asignacionesGrupo as grupos_periodo_actual' => fn ($query) => $query->where('periodo_id', $periodoId)])
            ->when($seccion === 'pendientes', fn ($query) => $query->where('status', 'pendiente'))
            ->when($seccion === 'activos', fn ($query) => $query->where('status', 'aprobado')->whereIn('role', $operativos))
            ->when($seccion === 'inactivos', fn ($query) => $query->where('status', 'bloqueado'))
            ->when($estado !== '', fn ($query) => $query->where('status', $estado))
            ->when($rol !== '', fn ($query) => $query->where('role', $rol))
            ->when($buscar !== '', function ($query) use ($buscar) {
                $like = '%'.addcslashes($buscar, '%_\\').'%';
                $query->where(fn ($where) => $where->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
        $comunidades = Comunidad::orderBy('comunidad')->get(['id', 'comunidad']);

        return view('secretaria.usuarios.index', compact('usuarios', 'comunidades', 'estado', 'rol', 'buscar', 'perPage', 'seccion', 'resumen'));
    }

    public function reactivar(Request $request, User $user, AccountManager $accounts): RedirectResponse
    {
        abort_unless($request->user()->operationalRole() === UserRole::Secretaria, 403);

        return $this->run(fn () => $accounts->reactivate($request->user(), $user));
    }

    public function comunidad(Request $request, User $user, AccountManager $accounts): RedirectResponse
    {
        abort_unless($request->user()->operationalRole() === UserRole::Secretaria, 403);

        $data = $request->validate([
            'comunidad_id' => ['required', 'integer', Rule::exists('comunidades', 'id')->whereNull('deleted_at')],
        ], [
            'comunidad_id.required' => 'Selecciona la comunidad que coordinará.',
        ]);

        return $this->run(fn () => $accounts->changeCommunity($request->user(), $user, (int) $data['comunidad_id']));
    }

    public function rol(Request $request, User $user, AccountManager $accounts): RedirectResponse
    {
        abort_unless($request->user()->operationalRole() === UserRole::Secretaria, 403);

        $data = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
            'comunidad_id' => [
                Rule::requiredIf($request->input('role') === UserRole::CoordinadorComunidades->value),
                'nullable', 'integer', Rule::exists('comunidades', 'id')->whereNull('deleted_at'),
            ],
        ], [
            'comunidad_id.required' => 'Selecciona la comunidad que coordinará.',
        ]);
        $comunidadId = isset($data['comunidad_id']) ? (int) $data['comunidad_id'] : null;
        $periodoId = session('periodo_activo_id') ? (int) session('periodo_activo_id') : null;

        return $this->run(fn () => $accounts->changeRole($request->user(), $user, UserRole::from($data['role']), $comunidadId, $periodoId));
    }

    private function run(Closure $action): RedirectResponse
    {
        try {
            return back()->with('status', $action());
        } catch (AccountActionRejected $rejected) {
            return back()->with('error', $rejected->getMessage());
        }
    }
}
