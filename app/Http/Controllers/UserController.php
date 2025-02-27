<?php
    
namespace App\Http\Controllers;
    
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
    
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
        $this->middleware('permission:Mostrar usuario|Crear usuario|Editar usuario|Borrar usuario', ['only' => ['index','store']]);
        $this->middleware('permission:Crear usuario', ['only' => ['create','store']]);
        $this->middleware('permission:Editar usuario', ['only' => ['edit','update']]);
        $this->middleware('permission:Borrar usuario', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = User::where([
            ['name', '!=', Null],
            [function($query) use ($request){
                if (($term = $request->term)){
                    $query->orWhere('name', 'LIKE', '%'.$term.'%')->get();
                }
            }]
        ])
            ->orderBy("id","desc")
            ->paginate(10);

        //$data = User::orderBy('id','DESC')->paginate(5);
        return view('users.index',compact('data'))
            ->with('i', ($request->input('page', 1) - 1) * 10);
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::pluck('name','name')->all();
        return view('users.create',compact('roles'));
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
            'nombre' => 'required|unique:users,name',
            'email' => 'required|email|unique:users,email',
            'contraseña' => 'required|min:8|same:confirmar-contraseña|',
            'roles' => 'required'
        ]);

        //required|string|confirmed|
    
        $input = [
            'name' => $request->nombre,
            'email' => $request->email,
            'password' => $request->contraseña,
            'roles' => $request->roles,
              
        ];
        $input['password'] = Hash::make($input['password']);
    
        $user = User::create($input);
        $user->assignRole($request->input('roles'));
    
        return redirect()->route('users.index')
                        ->with('success','Usuario creado');
    }
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::find($id);
        return view('users.show',compact('user'));
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
        $userArray = [
            $nombre = $user->name,
            
        ];
        return view('users.edit',compact('user','roles','userRole','userArray'));
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
            'email' => 'required|email|unique:users,email,'.$id,
            'password' => 'same:confirm-password',
            'roles' => 'required'
        ]);
    
        $input = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->contraseña,
            'roles' => $request->roles,
              
        ];

        //$input = $request->all();
        if(!empty($input['password'])){ 
            $input['password'] = Hash::make($input['password']);
        }else{
            $input = Arr::except($input,array('password'));    
        }
    
        $user = User::find($id);
        $user->update($input);
        DB::table('model_has_roles')->where('model_id',$id)->delete();
    
        $user->assignRole($request->input('roles'));
    
        return redirect()->route('users.index')
                        ->with('success','El usuario ha sido actualizado');
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
        return redirect()->route('users.index')
                        ->with('success','El usuario ha sido borrado');
    }
}