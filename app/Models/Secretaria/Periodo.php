<?php

namespace App\Models\Secretaria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Periodo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'periodos';

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'estado' => 'integer',
    ];

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'periodo_id');
    }

    public function asignacionesGrupo()
    {
        return $this->hasMany(AsignaGrupo::class, 'periodo_id');
    }

    public function getPeriodoTextoAttribute()
    {
        return $this->fecha_inicio?->format('Y-m-d') . ' al ' . $this->fecha_fin?->format('Y-m-d');
    }

    public function grupos()
    {
        return $this->hasMany(Grupo::class, 'periodo_id');
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class, 'periodo_id');
    }
}
