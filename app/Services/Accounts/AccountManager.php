<?php

namespace App\Services\Accounts;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserStatusChange;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Acciones de Secretaría sobre cuentas: bloquear, reactivar, cambiar comunidad y cambiar rol.
 * Protecciones comunes: nadie actúa sobre su propio estado o rol, y nunca queda el sistema sin
 * una cuenta de Secretaría aprobada. Cada cambio queda en user_status_changes y en el log.
 */
class AccountManager
{
    public function block(User $actor, User $target): string
    {
        if ($target->is($actor)) {
            throw new AccountActionRejected('No puedes bloquear tu propia cuenta.');
        }

        return DB::transaction(function () use ($actor, $target) {
            $otherSecretarias = $this->lockOtherApprovedSecretarias($target);
            $user = $this->lock($target);

            if ($user->status === 'bloqueado') {
                throw new AccountActionRejected('La cuenta ya está bloqueada.');
            }
            if ($this->isApprovedSecretaria($user) && $otherSecretarias === 0) {
                throw new AccountActionRejected('No se puede bloquear: debe quedar al menos una cuenta de Secretaría aprobada.');
            }

            $this->apply($actor, $user, 'bloquear', ['status' => 'bloqueado', 'approved_at' => null, 'approved_by' => null]);

            return "La cuenta de {$user->name} quedó bloqueada.";
        });
    }

    public function reactivate(User $actor, User $target): string
    {
        return DB::transaction(function () use ($actor, $target) {
            $user = $this->lock($target);

            if ($user->status !== 'bloqueado') {
                throw new AccountActionRejected('Solo se pueden reactivar cuentas bloqueadas.');
            }

            // Sin rol operativo (por ejemplo, una solicitud rechazada), vuelve a la bandeja de pendientes.
            if ($user->operationalRole() === null) {
                $this->apply($actor, $user, 'reactivar', ['status' => 'pendiente']);

                return "La solicitud de {$user->name} volvió a Usuarios pendientes.";
            }

            $this->apply($actor, $user, 'reactivar', ['status' => 'aprobado', 'approved_at' => now(), 'approved_by' => $actor->id]);

            return "La cuenta de {$user->name} quedó reactivada.";
        });
    }

    public function changeCommunity(User $actor, User $target, int $comunidadId): string
    {
        return DB::transaction(function () use ($actor, $target, $comunidadId) {
            $user = $this->lock($target);

            if ($user->operationalRole() !== UserRole::CoordinadorComunidades) {
                throw new AccountActionRejected('Solo el Coordinador de Comunidades tiene comunidad asignada.');
            }

            $this->apply($actor, $user, 'cambiar_comunidad', ['comunidad_id' => $comunidadId]);

            return "La comunidad de {$user->name} se actualizó.";
        });
    }

    public function changeRole(User $actor, User $target, UserRole $role, ?int $comunidadId, ?int $periodId): string
    {
        if ($target->is($actor)) {
            throw new AccountActionRejected('No puedes cambiar tu propio rol.');
        }

        return DB::transaction(function () use ($actor, $target, $role, $comunidadId, $periodId) {
            $otherSecretarias = $this->lockOtherApprovedSecretarias($target);
            $user = $this->lock($target);

            if ($user->status !== 'aprobado') {
                throw new AccountActionRejected('Solo se puede cambiar el rol de cuentas aprobadas.');
            }
            if ($user->operationalRole() === $role) {
                throw new AccountActionRejected('La cuenta ya tiene ese rol.');
            }
            if ($this->isApprovedSecretaria($user) && $otherSecretarias === 0) {
                throw new AccountActionRejected('No se puede cambiar: debe quedar al menos una cuenta de Secretaría aprobada.');
            }
            if ($user->operationalRole() === UserRole::Catequista && ($groups = $this->assignedGroups($user, $periodId)) > 0) {
                throw new AccountActionRejected('Este catequista tiene '.self::groupsPhrase($groups)
                    .' en el periodo actual. Reasígnalos antes de cambiar su rol.');
            }
            if ($role === UserRole::CoordinadorComunidades && ! $comunidadId) {
                throw new AccountActionRejected('Selecciona la comunidad que coordinará.');
            }

            $changes = ['role' => $role->value];
            if ($role === UserRole::CoordinadorComunidades) {
                $changes['comunidad_id'] = $comunidadId;
            } elseif ($user->comunidad_id !== null) {
                // La comunidad solo tiene sentido para el Coordinador de Comunidades.
                $changes['comunidad_id'] = null;
            }
            $this->apply($actor, $user, 'cambiar_rol', $changes);

            return "El rol de {$user->name} ahora es {$role->label()}.";
        });
    }

    /** Grupos asignados al catequista en el periodo indicado (0 si no hay periodo). */
    public function assignedGroups(User $user, ?int $periodId): int
    {
        return $periodId ? $user->asignacionesGrupo()->where('periodo_id', $periodId)->count() : 0;
    }

    public static function groupsPhrase(int $count): string
    {
        return $count === 1 ? '1 grupo asignado' : "{$count} grupos asignados";
    }

    /** Bloquea las filas de las demás Secretarías aprobadas hasta el final de la transacción y las cuenta. */
    private function lockOtherApprovedSecretarias(User $target): int
    {
        return User::where('role', UserRole::Secretaria->value)->where('status', 'aprobado')
            ->whereKeyNot($target->getKey())->lockForUpdate()->count();
    }

    private function lock(User $target): User
    {
        return User::whereKey($target->getKey())->lockForUpdate()->firstOrFail();
    }

    private function isApprovedSecretaria(User $user): bool
    {
        return $user->isApprovedForAccess() && $user->operationalRole() === UserRole::Secretaria;
    }

    private function apply(User $actor, User $user, string $action, array $changes): void
    {
        $before = [$user->status, $user->role, $user->comunidad_id];
        $user->update($changes);

        $change = [
            'user_id' => $user->id,
            'changed_by' => $actor->id,
            'action' => $action,
            'from_status' => $before[0],
            'to_status' => $user->status,
            'from_role' => $before[1],
            'to_role' => $user->role,
            'from_comunidad_id' => $before[2],
            'to_comunidad_id' => $user->comunidad_id,
        ];
        UserStatusChange::create($change);
        // El log de producción se conserva 14 días; user_status_changes es el historial permanente.
        Log::info('cuentas.cambio', $change);
    }
}
