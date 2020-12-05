<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\UserGroup;
use App\UserGroupRole;
use Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();
        $user_groups = UserGroup::all();

        return response()->json([
            'users' => $users,
            'user_groups' => $user_groups
        ], 200);
    }

    public function getAuthUser(){
        $user = Auth::user();
        $roles = UserGroup::select('user_roles.name')
                ->join('user_group_roles', 'user_group_roles.user_group_id', '=', 'user_groups.id')
                ->join('user_roles', 'user_roles.id', '=', 'user_group_roles.user_role_id')
                ->where('user_groups.id', '=', $user->user_group_id)
                ->get();
        $user->roles = $roles;
        return response()->json($user);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => ['required'],
            'last_name' => ['required'],
            'group_id' => ['required'],
            'email' => 'required|email|unique:users',
            'password' => ['required']
        ]);

            $user = new User;

            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->user_group_id = $request->group_id;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->created_by = Auth::id();
            $user->updated_by = Auth::id();
            $user->save();


            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $user->created_by = $auth_user_full_name;
            $user->updated_by = $auth_user_full_name;
            return response()->json($user, 200);
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
        //
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
        $request->validate([
            'first_name' => ['required'],
            'last_name' => ['required'],
            'group_id' => ['required'],
            'email' => 'required|email|unique:users,email,' . $id,
        ]);

            $user = User::findOrFail($id);

            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->user_group_id = $request->group_id;
            $user->email = $request->email;
            
            if(!empty($request->password)){
                $user->password = Hash::make($request->password);
            }

            $user->updated_by = Auth::id();
            $user->save();

            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $user->updated_by = $auth_user_full_name;
            return response()->json($user, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function has_role($group_id, $role_id){
        
        return UserGroupRole::where(['user_group_id' => $group_id, 'user_role_id' => $role_id])->first();

    }

    public function login(Request $request){

        $request->validate([
            'email'     => ['required', 'email'],
            'password'  => ['required']
        ]);

        $user = User::where('email' , $request->email)->first();
        if(!$user){
            
            throw ValidationException::withMessages([
                'email' => 'Les informations fournies sont incorrectes'
            ]);
        }
        

        if(!Hash::check($request->password, $user->password)) {

            throw ValidationException::withMessages([
                'email' => 'Les informations fournies sont incorrectes'
            ]);
        }


        if(!$user->group->hasRole('POST_AUTH')){

            throw ValidationException::withMessages([
                'email' => 'Accés interdit'
            ]);

        }

        return response()->json([ 'token' => $user->createToken('Auth Token')->accessToken ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
    }
}
