<?php

namespace App\Http\Controllers;

use App\CustomerActivity;
use App\Lang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; 
use Auth; 

class CustomerActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return CustomerActivity::select('customer_activities.*',
         'multilang_contents.name as label',
         'multilang_contents.name',
          'multilang_contents.description',
         'langs.full_name as lang',
         'langs.id as lang_id' ,
         DB::raw('CONCAT(user_created_by.first_name , " " , user_created_by.last_name) as created_by'),
         DB::raw('CONCAT(user_updated_by.first_name , " " , user_updated_by.last_name) as updated_by'))
            ->join('multilang_contents', 'customer_activities.id', '=', 'multilang_contents.module_id')
            ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
            ->leftJoin('users as user_created_by', 'user_created_by.id', '=', 'customer_activities.created_by')
            ->leftJoin('users as user_updated_by', 'user_updated_by.id', '=', 'customer_activities.updated_by')
            ->where('multilang_contents.module', 'CUSTOMER_ACTIVITY')
            ->where('langs.id', 1)
            ->orderBy('customer_activities.id', 'ASC')
            ->get();
    }

    public function index2()
    {
        return CustomerActivity::select('customer_activities.id',
         'multilang_contents.name as label',
         'multilang_contents.name',
         'multilang_contents.description',
         'multilang_contents.lang_id')
            ->join('multilang_contents', 'customer_activities.id', '=', 'multilang_contents.module_id')
            ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
            ->where('multilang_contents.module', 'CUSTOMER_ACTIVITY')
            ->where([
                'customer_activities.is_activated' => 1
            ])
            ->orderBy('customer_activities.id', 'ASC')
            ->get();
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
            'is_activated' => ['required'],
            'rows.*.name' => ['required'],
                
            ]);

            $activity = new CustomerActivity;

            $activity->is_activated = $request->is_activated;
            $activity->created_by = Auth::id();
            $activity->updated_by = Auth::id();


            DB::transaction(function () use ($request, $activity) {

                $activity->save();

                $data = [];
                foreach (json_decode($request->rows, true) as $key => $row) {

                    array_push($data, [
                        'name' => $row['name'],
                        'description' => $row['description'],
                        'lang_id' => $row['lang_id'],
                        'module' => 'CUSTOMER_ACTIVITY',
                        'module_id' => $activity->id
                    ]);
                }

                DB::table('multilang_contents')->insert($data);

            });
            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $activity->created_by = $auth_user_full_name;
            $activity->updated_by = $auth_user_full_name;
            return response()->json([$activity], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CustomerActivity  $customerActivity
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $activity = CustomerActivity::select('customer_activities.*', 
            DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
            DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
            ->leftJoin('users as created_by', 'created_by.id', '=', 'customer_activities.created_by')
            ->leftJoin('users as updated_by', 'updated_by.id', '=', 'customer_activities.updated_by')
            ->where(['customer_activities.id' => $id])
            ->first();
            
            $activity->fields = DB::table('multilang_contents')
            ->where([
                'module' => 'CUSTOMER_ACTIVITY',
                'module_id' => $id
            ])->get();
            
    
            return response()->json($activity, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\CustomerActivity  $customerActivity
     * @return \Illuminate\Http\Response
     */
    public function edit(CustomerActivity $customerActivity)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\CustomerActivity  $customerActivity
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'is_activated' => ['required'],
            'rows.*.name' => ['required' ]
            ]);

            $activity = CustomerActivity::findOrFail($id);

            $activity->is_activated = $request->is_activated;
            $activity->updated_by = Auth::id();

            DB::transaction(function () use ($request, $activity, $id) {

                $activity->save();

                $data = [];
                foreach (json_decode($request->rows, true) as $key => $row) {

                    array_push($data, [
                        'name' => $row['name'],
                        'description' => $row['description'],
                        'module' => 'CUSTOMER_ACTIVITY',
                        'lang_id' => $row['lang_id'],
                        'module_id' => $activity->id
                    ]);
                }

                DB::table('multilang_contents')->where(['module_id' => $id, 'module' => 'CUSTOMER_ACTIVITY'])->delete();
                DB::table('multilang_contents')->insert($data);

            });
            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $activity->updated_by = $auth_user_full_name;
            return response()->json([$activity], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\CustomerActivity  $customerActivity
     * @return \Illuminate\Http\Response
     */
    public function destroy(CustomerActivity $customerActivity)
    {
        //
    }
}
