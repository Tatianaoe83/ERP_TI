<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gerencias_usuarios extends Model
{
    use HasFactory;

    public $table = 'gerencias_usuarios';

    public $fillable = [
        'id', 'users_id','GerenciaID'
    ];


    public function usuarios(){
        $this->belongsTo(User::class,'users_id','id');
     }

}
