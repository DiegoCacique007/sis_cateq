<?php
namespace App\Models\CoordGeneral;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nivel extends Model {
    use HasFactory, SoftDeletes;
    protected $table = 'niveles';
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
}
