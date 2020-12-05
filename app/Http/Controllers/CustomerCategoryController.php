<?php

namespace App\Http\Controllers;

use App\CustomerCategory;
use App\Lang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; 
use Auth; 
class CustomerCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return CustomerCategory::select('customer_categories.*',
         'multilang_contents.name as label',
         'multilang_contents.name',
          'multilang_contents.description',
         'langs.full_name as lang',
         'langs.id as lang_id' ,
         DB::raw('CONCAT(user_created_by.first_name , " " , user_created_by.last_name) as created_by'),
         DB::raw('CONCAT(user_updated_by.first_name , " " , user_updated_by.last_name) as updated_by'))
            ->join('multilang_contents', 'customer_categories.id', '=', 'multilang_contents.module_id')
            ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
            ->leftJoin('users as user_created_by', 'user_created_by.id', '=', 'customer_categories.created_by')
            ->leftJoin('users as user_updated_by', 'user_updated_by.id', '=', 'customer_categories.updated_by')
            ->where('multilang_contents.module', 'CUSTOMER_CATEGORY')
            ->where('langs.id', 1)
            ->orderBy('customer_categories.id', 'ASC')
            ->get();
    }

    public function index2()
    {
        return CustomerCategory::select('customer_categories.id',
         'multilang_contents.name as label',
         'multilang_contents.name',
         'multilang_contents.description',
         'multilang_contents.lang_id')
            ->join('multilang_contents', 'customer_categories.id', '=', 'multilang_contents.module_id')
            ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
            ->where('multilang_contents.module', 'CUSTOMER_CATEGORY')
            ->where([
                'customer_categories.is_activated' => 1
            ])
            ->orderBy('customer_categories.id', 'ASC')
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

            $category = new CustomerCategory;

            $category->is_activated = $request->is_activated;
            $category->created_by = Auth::id();
            $category->updated_by = Auth::id();


            DB::transaction(function () use ($request, $category) {

                $category->save();

                $data = [];
                foreach (json_decode($request->rows, true) as $key => $row) {

                    array_push($data, [
                        'name' => $row['name'],
                        'description' => $row['description'],
                        'lang_id' => $row['lang_id'],
                        'module' => 'CUSTOMER_CATEGORY',
                        'module_id' => $category->id
                    ]);
                }

                DB::table('multilang_contents')->insert($data);

            });
            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $category->created_by = $auth_user_full_name;
            $category->updated_by = $auth_user_full_name;
            return response()->json([$category], 200);

        }
    

        /**
         * Display the specified resource.
         *
         * @param  \App\CustomerCategory  $CustomerCategory
         * @return \Illuminate\Http\Response
         */
        public function show($id)
        {

            $category = CustomerCategory::select('customer_categories.*', 
            DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
            DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
            ->leftJoin('users as created_by', 'created_by.id', '=', 'customer_categories.created_by')
            ->leftJoin('users as updated_by', 'updated_by.id', '=', 'customer_categories.updated_by')
            ->where(['customer_categories.id' => $id])
            ->first();
            
            $category->fields = DB::table('multilang_contents')
            ->where([
                'module' => 'CUSTOMER_CATEGORY',
                'module_id' => $id
            ])->get();
            
    
            return response()->json($category, 200);
        }

        /**
         * Show the form for editing the specified resource.
         *
         * @param  \App\CustomerCategory  $CustomerCategory
         * @return \Illuminate\Http\Response
         */
        public function edit(CustomerCategory $CustomerCategory)
        {
        //
        }

        /**
         * Update the specified resource in storage.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  \App\CustomerCategory  $CustomerCategory
         * @return \Illuminate\Http\Response
         */
        public function update(Request $request, $id)
        {

            $request->validate([
                'is_activated' => ['required'],
                'rows.*.name' => ['required' ]
                ]);
    
                $category = CustomerCategory::findOrFail($id);
    
                $category->is_activated = $request->is_activated;
                $category->updated_by = Auth::id();
    
                DB::transaction(function () use ($request, $category, $id) {
    
                    $category->save();
    
                    $data = [];
                    foreach (json_decode($request->rows, true) as $key => $row) {
    
                        array_push($data, [
                            'name' => $row['name'],
                            'description' => $row['description'],
                            'module' => 'CUSTOMER_CATEGORY',
                            'lang_id' => $row['lang_id'],
                            'module_id' => $category->id
                        ]);
                    }

                    DB::table('multilang_contents')->where(['module_id' => $id, 'module' => 'CUSTOMER_CATEGORY'])->delete();
                    DB::table('multilang_contents')->insert($data);
    
                });
                $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
                $category->updated_by = $auth_user_full_name;
                return response()->json([$category], 200);
        
    }

        /**
         * Remove the specified resource from storage.
         *
         * @param  \App\CustomerCategory  $CustomerCategory
         * @return \Illuminate\Http\Response
         */
        public function destroy(Request $request, $id)
        {

        }

    }
