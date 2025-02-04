<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompaniasLineasTelefonicas extends Model
{
    use HasFactory;
    public $table = 'companiaslineastelefonicas';
    
    protected $primaryKey = 'ID';
    protected $keyType = 'int'; 

    protected $fillable = ['Compania'];
}
