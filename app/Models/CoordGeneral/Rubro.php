<?php
namespace App\Models\CoordGeneral;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rubro extends Model {
    use HasFactory, SoftDeletes;
    protected $table = 'rubros';
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
}
