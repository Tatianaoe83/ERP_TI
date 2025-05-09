<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

//agregamos lo siguiente
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Gerencias_usuarios;
use App\Models\Gerencia;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;

class UsuarioController extends Controller
{
    function __construct()
        {
            $this->middleware('permission:ver-usuarios|crear-usuarios|editar-usuarios|borrar-usuarios')->only('index');
            $this->middleware('permission:crear-usuarios', ['only' => ['create','store']]);
            $this->middleware('permission:editar-usuarios', ['only' => ['edit','update']]);
            $this->middleware('permission:borrar-usuarios', ['only' => ['destroy']]);
        }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {      
        //Sin paginación
        /* $usuarios = User::all();
        return view('usuarios.index',compact('usuarios')); */

        //Con paginación
        $usuarios = User::all();
        return view('usuarios.index',compact('usuarios'));

        //al usar esta paginacion, recordar poner en el el index.blade.php este codigo  {!! $usuarios->links() !!}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //aqui trabajamos con name de las tablas de users
        $roles = Role::pluck('name','name')->all();
        return view('usuarios.crear',compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'ApellPaterno' => 'required',
            'nombres' => 'required',
            'username' => 'required|unique:users,username',
            'password' => 'required|same:confirm-password',
            'roles' => 'required'
        ]);

        
        $input = $request->all();
        $input['name'] = $request->ApellPaterno.' '.$request->ApellMaterno.' '. $request->nombres;

        $input['password'] = Hash::make($input['password']);
    
        $user = User::create($input);
        $user->assignRole($request->input('roles'));

        $IdUsuario = $user->id;

        if ($request->gerenci_id != null){
            foreach ($request->gerenci_id as $key => $value) {
                Gerencias_usuarios::create([
                            'GerenciaID' => $value,
                            'users_id' => $IdUsuario,
                ]);
                }
        }
    
        return redirect()->route('usuarios.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::find($id);
        $roles = Role::pluck('name','name')->all();
        $userRole = $user->roles->pluck('name','name')->all();
        $geren = Gerencia::pluck('NombreGerencia','GerenciaID')->all();
        $gerenUsu= DB::select("SELECT GerenciaID FROM gerencias_usuarios WHERE users_id = $id");
        $gerenUsuarios = array_column($gerenUsu, 'GerenciaID');
    
        return view('usuarios.editar',compact('user','roles','userRole','geren','gerenUsuarios'));
    }
    

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
      
        $this->validate($request, [
            'name' => 'required',
            'password' => 'same:confirm-password',
            'roles' => 'required'
        ]);
    
        $input = $request->all();
        if(!empty($input['password'])){ 
            $input['password'] = Hash::make($input['password']);
        }else{
            $input = Arr::except($input,array('password'));    
        }
    
        $user = User::find($id);
        $user->update($input);
        DB::table('model_has_roles')->where('model_id',$id)->delete();
    
        $user->assignRole($request->input('roles'));
    
        $IdUsuario = $user->id;


        if ($request->gerenci_id != null){
                
                    $gerenEmp= DB::select("SELECT GerenciaID FROM gerencias_usuarios WHERE users_id = $IdUsuario");
                    $gerenToEmp = array_column($gerenEmp, 'GerenciaID');

                    $resultado = array_diff($request->gerenci_id, $gerenToEmp);


                    $resultado2 = array_diff($gerenToEmp, $request->gerenci_id);

                
                    foreach ($resultado as $key => $value) {
                        Gerencias_usuarios::create([
                            'GerenciaID' => $value,
                            'users_id' => $IdUsuario,
                            ]);
                        }
                    foreach ($resultado2 as $key => $value2) {
                        Gerencias_usuarios::where('users_id', $IdUsuario)
                        ->where('GerenciaID',$value2)
                        ->delete();
                        }
                }else{
                
                    $gerenEmp= DB::select("SELECT GerenciaID FROM gerencias_usuarios WHERE users_id = $IdUsuario");
                    $gerenToEmp = array_column($gerenEmp, 'GerenciaID');
                    foreach ($gerenToEmp as $key => $value2) {
                    
                        Gerencias_usuarios::where('users_id', $IdUsuario)
                        ->where('GerenciaID',$value2)
                        ->delete();
                        }
                    
        }

        return redirect()->route('usuarios.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        User::find($id)->delete();
        return redirect()->route('usuarios.index');
    }
}
