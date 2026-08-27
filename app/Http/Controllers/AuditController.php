<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Audit;
use DB;


class AuditController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:ver-informe|buscar-informe')->only(['index', 'getAudits']);
    }

    public function index()
    {

        $usuarios= Audit::select(DB::raw('distinct(user_id)'))
        ->pluck('user_id')
        ->toArray();

        $tablas= Audit::select(DB::raw('distinct(auditable_type)'))
        ->pluck('auditable_type')
        ->toArray();

      
        return view('audits.index',compact('usuarios','tablas'));
    }

    public function getAudits(Request $request)
    {

       
        $query = Audit::join('users', 'audits.user_id', '=', 'users.id')
            ->select([
                'audits.id',
                'users.name',
                'audits.auditable_type',
                'audits.auditable_id',
                'audits.old_values',
                'audits.new_values',
                'audits.created_at'
            ]);

        if ($request->filled('user_type')) {
            $query->where('users.id', $request->input('user_type'));
        }

        if ($request->filled('auditable_type')) {
            $query->where('audits.auditable_type', $request->input('auditable_type'));
        }

        if ($request->filled('new_values')) {
            $term = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $request->input('new_values')));
            $like = '%' . $term . '%';
            $query->where(function ($q) use ($like) {
                $q->where('audits.new_values', 'like', $like)
                    ->orWhere('audits.old_values', 'like', $like)
                    ->orWhere('users.name', 'like', $like)
                    ->orWhere('audits.auditable_id', 'like', $like);
            });
        }

        return datatables()->of($query)->make(true);
    }


}
