<?php

namespace App\Models\Secretaria;

use App\Models\Concerns\PreventsDeletionWithDependents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grupo extends Model
{
    use HasFactory, PreventsDeletionWithDependents, SoftDeletes;

    protected function dependentRelations(): array
    {
        return [
            'inscripciones' => ['inscripción', 'inscripciones'],
            'asignacionesGrupo' => ['asignación de grupo', 'asignaciones de grupo'],
        ];
    }

    protected $table = 'grupos';

    protected $fillable = [
        'nombre',
        'periodo_id',
    ];

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'grupo_id');
    }

    public function asignacionesGrupo()
    {
        return $this->hasMany(AsignaGrupo::class, 'grupo_id');
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }
}
