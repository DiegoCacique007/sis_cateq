<?php

namespace App\Models\Secretaria;

use App\Models\Concerns\PreventsDeletionWithDependents;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comunidad extends Model
{
    use HasFactory, PreventsDeletionWithDependents, SoftDeletes;

    protected $table = 'comunidades';

    protected $fillable = [
        'comunidad',
    ];

    public function alumnos()
    {
        return $this->hasMany(Alumno::class, 'comunidad_id');
    }

    public function asignacionesGrupo()
    {
        return $this->hasMany(AsignaGrupo::class, 'comunidad_id');
    }

    /** Cuentas con esta comunidad (coordinación). users no usa borrado lógico: cuentan todas, de cualquier rol y estado. */
    public function usuarios()
    {
        return $this->hasMany(User::class, 'comunidad_id');
    }

    protected function dependentRelations(): array
    {
        return [
            'alumnos' => ['alumno', 'alumnos'],
            'asignacionesGrupo' => ['asignación de grupo', 'asignaciones de grupo'],
            'usuarios' => ['cuenta de usuario', 'cuentas de usuario'],
        ];
    }
}
