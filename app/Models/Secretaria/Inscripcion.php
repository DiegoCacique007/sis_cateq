<?php

namespace App\Models\Secretaria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inscripcion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inscripciones';

    protected $fillable = [
        'alumno_id',
        'periodo_id',
        'grupo_id',
        'asigna_grupo_id',
        'estado',
    ];

    protected $casts = [
        'estado' => 'integer',
    ];

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'alumno_id');
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class, 'inscripcion_id');
    }

    public function asignaGrupo()
    {
        return $this->belongsTo(AsignaGrupo::class, 'asigna_grupo_id');
    }

    public function getEstadoTextoAttribute()
    {
        return $this->estado == 1 ? 'Alta' : 'Baja';
    }
}
