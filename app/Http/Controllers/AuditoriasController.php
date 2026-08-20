<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuditoriasController extends Controller
{
    public function index()
    {
        return view('auditorias.index');
    }
}
