<?php

namespace App\Models\Secretaria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comunidad extends Model
{
    use HasFactory, SoftDeletes;

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
}
