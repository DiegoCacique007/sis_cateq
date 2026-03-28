<?php
namespace App\Models\Secretaria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alumno extends Model {
    use HasFactory, SoftDeletes;
    protected $table = 'alumnos';
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
}
