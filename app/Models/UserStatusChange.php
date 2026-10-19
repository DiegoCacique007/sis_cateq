<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Fila del historial de cambios de cuenta hechos por Secretaría. Solo se inserta; nunca se modifica. */
class UserStatusChange extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'changed_by',
        'action',
        'from_status',
        'to_status',
        'from_role',
        'to_role',
        'from_comunidad_id',
        'to_comunidad_id',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'changed_by' => 'integer',
            'from_comunidad_id' => 'integer',
            'to_comunidad_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
