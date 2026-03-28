<?php
namespace App\Models\Catequista;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evaluacion extends Model {
    use HasFactory, SoftDeletes;

    protected $table = 'evaluaciones'; // Nombre de tu tabla en la BD
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
}
