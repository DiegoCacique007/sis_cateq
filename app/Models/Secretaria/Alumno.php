<?php

namespace App\Models\Secretaria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alumno extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alumnos';

    protected $fillable = [
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'comunidad_id',
        'estado',
        'fecha_nacimiento',
        // Datos de Bautizo
        'bautizo_lugar',
        'bautizo_fecha',
        'bautizo_libro',
        'bautizo_acta',
        // Datos de Primera Comunión
        'primera_comunion_lugar',
        'primera_comunion_fecha',
        'primera_comunion_libro',
        'primera_comunion_acta',
    ];

    protected $casts = [
        'estado' => 'integer',
        'fecha_nacimiento' => 'date',
        'bautizo_fecha' => 'date',
        'primera_comunion_fecha' => 'date',
    ];

    public function comunidad()
    {
        return $this->belongsTo(Comunidad::class, 'comunidad_id');
    }

    public function tutores()
    {
        return $this->hasMany(Tutor::class, 'alumno_id');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'alumno_id');
    }

    public function getNombreCompletoAttribute()
    {
        return trim(
            $this->nombre . ' ' .
            $this->apellido_paterno . ' ' .
            ($this->apellido_materno ?? '')
        );
    }
}
