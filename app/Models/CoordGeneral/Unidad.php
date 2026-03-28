<?php
namespace App\Models\CoordGeneral;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unidad extends Model {
    use HasFactory, SoftDeletes;
    protected $table = 'unidades';
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
}
