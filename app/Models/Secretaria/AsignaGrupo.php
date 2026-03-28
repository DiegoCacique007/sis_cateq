<?php
namespace App\Models\Secretaria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsignaGrupo extends Model {
    use HasFactory, SoftDeletes;
    protected $table = 'asigna_grupo';
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
}
