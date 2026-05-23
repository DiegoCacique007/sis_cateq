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
        'fecha_nacimiento',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
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