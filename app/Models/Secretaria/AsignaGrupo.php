<?php

namespace App\Models\Secretaria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsignaGrupo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asigna_grupo';

    protected $fillable = [
        'comunidad_id',
        'grupo_id',
        'nivel_id',
        'periodo_id',
        'catequista_id',
    ];

    public function comunidad()
    {
        return $this->belongsTo(Comunidad::class, 'comunidad_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function catequista()
    {
        return $this->belongsTo(\App\Models\User::class, 'catequista_id');
    }
}
