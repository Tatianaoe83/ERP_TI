<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Audit;


class AuditController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:ver-informe')->only('index');
      
    }

    public function index()
    {
       
        $audits = Audit::join('users', 'audits.user_id', '=', 'users.id')
        ->select([
            'audits.id',
            'users.name',
            'audits.auditable_type',
            'audits.auditable_id',
            'audits.old_values',
            'audits.new_values',
            'audits.created_at'
        ])
        ->get();


        return view('audits.index', compact('audits'));
    }

}
