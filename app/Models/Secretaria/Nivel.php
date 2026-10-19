<?php

namespace App\Models\Secretaria;

use App\Models\Concerns\PreventsDeletionWithDependents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nivel extends Model
{
    use HasFactory, PreventsDeletionWithDependents, SoftDeletes;

    protected $table = 'niveles';

    protected $fillable = [
        'nivel',
    ];

    public function unidades()
    {
        return $this->hasMany(Unidad::class, 'nivel_id');
    }

    public function asignacionesGrupo()
    {
        return $this->hasMany(AsignaGrupo::class, 'nivel_id');
    }

    protected function dependentRelations(): array
    {
        return [
            'unidades' => ['unidad', 'unidades'],
            'asignacionesGrupo' => ['asignación de grupo', 'asignaciones de grupo'],
        ];
    }
}
