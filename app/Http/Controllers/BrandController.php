<?php

namespace App\Http\Controllers;

use App\Brand;
use App\Lang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; 
use Illuminate\Support\Facades\File; 
use Auth; 

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Brand::select('brands.*',
        'multilang_contents.name',
        'multilang_contents.name as label',
        'multilang_contents.description',
        'multilang_contents.page_title',
        'multilang_contents.meta_description',
        'multilang_contents.meta_keywords',
        'langs.full_name as lang',
        'langs.id as lang_id' ,
        DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
        DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
           ->join('multilang_contents', 'brands.id', '=', 'multilang_contents.module_id')
           ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
           ->join('users as created_by', 'created_by.id', '=', 'brands.created_by')
           ->leftJoin('users as updated_by', 'updated_by.id', '=', 'brands.updated_by')
           ->where('multilang_contents.module', 'BRAND')
           ->where('langs.id', 1)
           ->orderBy('brands.id', 'desc')
           ->get();
    }

    public function index2()
    {
        return Brand::select('brands.*',
        'multilang_contents.name',
        'multilang_contents.name as label',
        'multilang_contents.description',
        'multilang_contents.page_title',
        'multilang_contents.meta_description',
        'multilang_contents.meta_keywords',
        'langs.full_name as lang',
        'langs.id as lang_id' ,
        DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
        DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
           ->join('multilang_contents', 'brands.id', '=', 'multilang_contents.module_id')
           ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
           ->join('users as created_by', 'created_by.id', '=', 'brands.created_by')
           ->leftJoin('users as updated_by', 'updated_by.id', '=', 'brands.updated_by')
           ->where('multilang_contents.module', 'BRAND')
           ->orderBy('brands.id', 'desc')
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
            'rows.*.name' => ['required', 'unique:brands'],
            'image' => ['mimes:jpeg,jpg,png', 'max:10000']
            ]);

            $brand = new Brand;

            $brand->is_activated = $request->is_activated;
            $brand->created_by = Auth::id();
            $brand->updated_by = Auth::id();

            if (!empty($request->image)) {
                $imageName = time() . '.' . $request->image->getClientOriginalExtension();
                $brand->image = $imageName;
                $request->image->move(public_path('images'), $imageName);
            }

            DB::transaction(function () use ($request, $brand) {

                $brand->save();

                $data = [];
                foreach (json_decode($request->rows, true) as $key => $row) {

                    array_push($data, [
                        'name' => $row['name'],
                        'description' => $row['description'],
                        'page_title' => $row['page_title'],
                        'meta_description' => $row['meta_description'],
                        'meta_keywords' => $row['meta_keywords'],
                        'module' => 'BRAND',
                        'lang_id' => $row['lang_id'],
                        'module_id' => $brand->id
                    ]);
                }

                DB::table('multilang_contents')->insert($data);

            });
            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $brand->created_by = $auth_user_full_name;
            $brand->updated_by = $auth_user_full_name;
            return response()->json($brand, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Brand  $brand
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $brand = Brand::select('brands.*', 
        DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
        DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
        ->join('users as created_by', 'created_by.id', '=', 'brands.created_by')
        ->leftJoin('users as updated_by', 'updated_by.id', '=', 'brands.updated_by')
        ->where(['brands.id' => $id])
        ->first();

        $brand->fields = DB::table('multilang_contents')
        ->where([
            'module' => 'BRAND',
            'module_id' => $id
        ])->get();
        

        return response()->json($brand, 200);
    }

    public function show2($id)
    {
        $brand = Brand::select('brands.*', 
        'multilang_contents.name', 'multilang_contents.description', 'multilang_contents.lang_id')
        ->join('multilang_contents', 'multilang_contents.module_id', '=', 'brands.id')
        ->where(['brands.id' => $id,
        'multilang_contents.module' => 'BRAND'])
        ->get();

        return response()->json($brand, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Brand  $brand
     * @return \Illuminate\Http\Response
     */
    public function edit(Brand $brand)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Brand  $brand
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if(!empty($request->delete_image)){

            $brand = Brand::findOrFail($id);
            //File::delete(public_path('images/' . $brand->image));
            $brand->image = null;
            $brand->save();
            
            return response()->json([], 200);
        }
        else{

        $request->validate([
            'is_activated' => ['required'],
            'rows.*.name' => ['required'],
                'image' => ['mimes:jpeg,jpg,png', 'max:10000']
            ]);

            $brand = Brand::findOrFail($id);

            $brand->is_activated = $request->is_activated;
            $brand->updated_by = Auth::id();

            if (!empty($request->image)) {
                $imageName = time() . '.' . $request->image->getClientOriginalExtension();
                $brand->image = $imageName;
                $request->image->move(public_path('images'), $imageName);
            }

            DB::transaction(function () use ($request, $brand, $id) {

                $brand->save();

                $data = [];
                foreach (json_decode($request->rows, true) as $key => $row) {

                    array_push($data, [
                        'name' => $row['name'],
                        'description' => $row['description'],
                        'page_title' => $row['page_title'],
                        'meta_description' => $row['meta_description'],
                        'meta_keywords' => $row['meta_keywords'],
                        'lang_id' => $row['lang_id'],
                        'module' => 'BRAND',
                        'module_id' => $brand->id
                    ]);
                }

                DB::table('multilang_contents')->where(['module_id' => $id, 'module' => 'BRAND'])->delete();
                DB::table('multilang_contents')->insert($data);

            });
            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $brand->updated_by = $auth_user_full_name;
            return response()->json($brand, 200);
    }
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Brand  $brand
     * @return \Illuminate\Http\Response
     */
    public function destroy(Brand $brand)
    {
        //
    }
}
