<?php

namespace App\Http\Controllers;

use App\ProductCategory;
use App\Lang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; 
use Illuminate\Support\Facades\File; 
use Auth; 
class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return ProductCategory::select('product_categories.*',
         'multilang_contents.name as label',
         'multilang_contents.name',
         'multilang_contents.description',
         'multilang_contents.page_title',
         'multilang_contents.meta_description',
         'multilang_contents.meta_keywords',
         'langs.full_name as lang',
         'langs.id as lang_id' ,
         DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
         DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
            ->join('multilang_contents', 'product_categories.id', '=', 'multilang_contents.module_id')
            ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
            ->join('users as created_by', 'created_by.id', '=', 'product_categories.created_by')
            ->leftJoin('users as updated_by', 'updated_by.id', '=', 'product_categories.updated_by')
            ->where('multilang_contents.module', 'PRODUCT_CATEGORY')
            ->where('langs.id', 1)
            ->orderBy('product_categories.id', 'desc')
            ->get();
    }

    public function index2(){

        return ProductCategory::select('product_categories.*',
        'multilang_contents.name as label',
        'multilang_contents.name',
        'multilang_contents.description',
        'multilang_contents.page_title',
        'multilang_contents.meta_description',
        'multilang_contents.meta_keywords',
        'langs.full_name as lang',
        'langs.id as lang_id' )
           ->join('multilang_contents', 'product_categories.id', '=', 'multilang_contents.module_id')
           ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
           ->join('users as created_by', 'created_by.id', '=', 'product_categories.created_by')
           ->leftJoin('users as updated_by', 'updated_by.id', '=', 'product_categories.updated_by')
           ->where('multilang_contents.module', 'PRODUCT_CATEGORY')
           ->orderBy('product_categories.id', 'desc')
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
            'rows.*.name' => ['required',
            Rule::unique('multilang_contents')->where(function ($query) use ($request){
                return $query->join('product_categories', 'product_categories.id', '=', 'multilang_contents.product_category_id')
                ->where('product_categories.category_parent_id', $request->category_parent_id); })],
                'image' => ['mimes:jpeg,jpg,png', 'max:10000']
            ]);

            $category = new ProductCategory;

            $category->is_activated = $request->is_activated;
            $category->created_by = Auth::id();
            $category->updated_by = Auth::id();

            if (!empty($request->image)) {
                $imageName = time() . '.' . $request->image->getClientOriginalExtension();
                $category->image = $imageName;
                $request->image->move(public_path('images'), $imageName);

            }

            if (!empty($request->product_category_id)) {
                $category->product_category_id = $request->product_category_id;
            }
            else{
                $category->product_category_id = null;
            }

            DB::transaction(function () use ($request, $category) {

                $category->save();

                $data = [];
                foreach (json_decode($request->rows, true) as $key => $row) {

                    array_push($data, [
                        'name' => $row['name'],
                        'description' => $row['description'],
                        'page_title' => $row['page_title'],
                        'meta_description' => $row['meta_description'],
                        'meta_keywords' => $row['meta_keywords'],
                        'lang_id' => $row['lang_id'],
                        'module' => 'PRODUCT_CATEGORY',
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
         * @param  \App\ProductCategory  $productCategory
         * @return \Illuminate\Http\Response
         */
        public function show($id)
        {
            $category = ProductCategory::select('product_categories.*', 
            DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
            DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
            ->join('users as created_by', 'created_by.id', '=', 'product_categories.created_by')
            ->leftJoin('users as updated_by', 'updated_by.id', '=', 'product_categories.updated_by')
            ->where(['product_categories.id' => $id])
            ->first();
    
            $category->fields = DB::table('multilang_contents')
            ->where([
                'module' => 'PRODUCT_CATEGORY',
                'module_id' => $id
            ])->get();
            
    
            return response()->json($category, 200);
        }

        public function show2($id)
        {
            $category = ProductCategory::select('product_categories.*', 
            'multilang_contents.name', 'multilang_contents.description', 'multilang_contents.lang_id')
            ->join('multilang_contents', 'multilang_contents.module_id', '=', 'product_categories.id')
            ->where(['product_categories.id' => $id,
            'multilang_contents.module' => 'PRODUCT_CATEGORY'])
            ->get();
    
            return response()->json($category, 200);
        }

        /**
         * Show the form for editing the specified resource.
         *
         * @param  \App\ProductCategory  $productCategory
         * @return \Illuminate\Http\Response
         */
        public function edit(ProductCategory $productCategory)
        {
        //
        }

        /**
         * Update the specified resource in storage.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  \App\ProductCategory  $productCategory
         * @return \Illuminate\Http\Response
         */
        public function update(Request $request, $id)
        {
            if(!empty($request->delete_image)){

                $category = ProductCategory::findOrFail($id);
                File::delete(public_path('images/' . $category->image));
                $category->image = null;
                $category->save();
                
                return response()->json([], 200);
            }
            else{

            $request->validate([
                'is_activated' => ['required'],
                'rows.*.name' => ['required',
                Rule::unique('multilang_contents')->where(function ($query) use ($request){
                    return $query->join('product_categories', 'product_categories.id', '=', 'multilang_contents.product_category_id')
                    ->where('product_categories.category_parent_id', $request->category_parent_id); })],
                    'image' => ['mimes:jpeg,jpg,png', 'max:10000']
                ]);
    
                $category = ProductCategory::findOrFail($id);
    
                $category->is_activated = $request->is_activated;
                $category->updated_by = Auth::id();
    


                if (!empty($request->image)) {

                    if(file_exists(public_path('images/' . $category->image))){
                        File::delete(public_path('images/' . $category->image));
                    }

                    $imageName = time() . '.' . $request->image->getClientOriginalExtension();
                    $category->image = $imageName;
                    $request->image->move(public_path('images'), $imageName);
                }
    
                if (!empty($request->product_category_id)) {
                    $category->product_category_id = $request->product_category_id;
                }
    
                DB::transaction(function () use ($request, $category, $id) {
    
                    $category->save();
    
                    $data = [];
                    foreach (json_decode($request->rows, true) as $key => $row) {
    
                        array_push($data, [
                            'name' => $row['name'],
                            'description' => $row['description'],
                            'module' => 'PRODUCT_CATEGORY',
                            'page_title' => $row['page_title'],
                            'meta_description' => $row['meta_description'],
                            'meta_keywords' => $row['meta_keywords'],
                            'lang_id' => $row['lang_id'],
                            'module_id' => $category->id
                        ]);
                    }

                    DB::table('multilang_contents')->where(['module_id' => $id, 'module' => 'PRODUCT_CATEGORY'])->delete();
                    DB::table('multilang_contents')->insert($data);
    
                });
                $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
                $category->updated_by = $auth_user_full_name;
                return response()->json([$category], 200);
        }
    }

        /**
         * Remove the specified resource from storage.
         *
         * @param  \App\ProductCategory  $productCategory
         * @return \Illuminate\Http\Response
         */
        public function destroy(Request $request, $id)
        {

        }

    }
