<?php

namespace App\Models\Secretaria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tutor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tutores';

    protected $fillable = [
        'nombre',
        'ap',
        'am',
        'alumno_id',
    ];

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'alumno_id');
    }

    public function getNombreCompletoAttribute()
    {
        return trim(
            $this->nombre . ' ' .
            $this->ap . ' ' .
            ($this->am ?? '')
        );
    }
}
