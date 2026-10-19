<?php

namespace App\Models\Secretaria;

use App\Models\Concerns\PreventsDeletionWithDependents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rubro extends Model
{
    use HasFactory, PreventsDeletionWithDependents, SoftDeletes;

    protected function dependentRelations(): array
    {
        return ['evaluaciones' => ['evaluación', 'evaluaciones']];
    }

    protected $table = 'rubros';

    protected $fillable = [
        'nombre',
        'valor',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class, 'rubro_id');
    }
}
