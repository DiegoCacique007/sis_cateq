<?php
namespace App\Models\CoordGeneral;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Periodo extends Model {
    use HasFactory, SoftDeletes;
    protected $table = 'periodos';
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
}
