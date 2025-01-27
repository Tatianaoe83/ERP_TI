<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TiposDeCategorias extends Model
{
    use HasFactory;
    public $table = 'TiposDeCategorias';
    protected $primaryKey = 'ID';
    protected $keyType = 'int'; 

    protected $fillable = ['Categoria'];
}
