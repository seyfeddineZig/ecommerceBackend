<?php

namespace App\Http\Controllers;

use App\UserGroup;
use App\UserGroupRole;
use App\UserRole;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; 
use Auth; 

class UserGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_groups = UserGroup::all();
        $user_group_roles = UserGroupRole::all();
        $user_roles = UserRole::all();

        return response()->json([
            'user_groups' => $user_groups,
            'user_group_roles' => $user_group_roles,
            'user_roles' => $user_roles
        ], 200);
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
            'name' => ['required']
        ]);

            $user_group = new UserGroup;

            $user_group->name = $request->name;
            $user_group->description = $request->description;
            $user_group->created_by = Auth::id();
            $user_group->updated_by = Auth::id();


            DB::transaction(function () use ($request, $user_group) {

                $user_group->save();

                $data = [];
                foreach (json_decode($request->roles, true) as $role) {

                    array_push($data, [
                        'user_group_id' => $user_group->id,
                        'user_role_id' => $role,
                    ]);
                }

                DB::table('user_group_roles')->insert($data);

            });
            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $user_group->created_by = $auth_user_full_name;
            $user_group->updated_by = $auth_user_full_name;
            return response()->json($user_group, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\UserGroup  $userGroup
     * @return \Illuminate\Http\Response
     */
    public function show(UserGroup $userGroup)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\UserGroup  $userGroup
     * @return \Illuminate\Http\Response
     */
    public function edit(UserGroup $userGroup)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\UserGroup  $userGroup
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => ['required']
        ]);

            $user_group = UserGroup::findOrFail($id);

            $user_group->name = $request->name;
            $user_group->description = $request->description;
            $user_group->updated_by = Auth::id();


            DB::transaction(function () use ($request, $user_group) {

                $user_group->save();

                $data = [];
                foreach (json_decode($request->roles, true) as $role) {

                    array_push($data, [
                        'user_group_id' => $user_group->id,
                        'user_role_id' => $role,
                    ]);
                }

                DB::table('user_group_roles')->where(['user_group_id' => $user_group->id])->delete();
                DB::table('user_group_roles')->insert($data);

            });
            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $user_group->updated_by = $auth_user_full_name;
            return response()->json($user_group, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\UserGroup  $userGroup
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserGroup $userGroup)
    {
        //
    }
}
