<?php
namespace App\Models\CoordComunidad; // <--- Ahora Laravel sabe que está en esta subcarpeta

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comunidad extends Model {
    use HasFactory, SoftDeletes;

    protected $table = 'comunidades';
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
}
