<?php
namespace App\Models\Secretaria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inscripcion extends Model {
    use HasFactory, SoftDeletes;
    protected $table = 'inscripciones';
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
}
