<?php

namespace App\Models\Secretaria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nivel extends Model
{
    use HasFactory, SoftDeletes;

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
}
