<?php

namespace App\Models\Secretaria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unidad extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'unidades';

    protected $fillable = [
        'nivel_id',
        'numero',
        'nombre',
    ];

    protected $casts = [
        'nivel_id' => 'integer',
        'numero' => 'integer',
    ];

    public function nivel()
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class, 'unidad_id');
    }

    public function getUnidadTextoAttribute()
    {
        return 'Unidad ' . $this->numero . ' - ' . $this->nombre;
    }
}
