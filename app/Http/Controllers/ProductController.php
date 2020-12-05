<?php

namespace App\Http\Controllers;

use App\Product;
use App\ProductCategory;
use App\ProductPackge;
use App\AttributeValue;
use App\Lang;
use App\Brand;
use App\Pricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Imports\ExcelImport;
use Maatwebsite\Excel\Facades\Excel;
use Auth;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Product::select('products.*',
        'categories_lang.name as category_name',
        'sub_categories_lang.name as sub_category_name',
        'multilang_contents.name as label',
        'multilang_contents.name',
        'multilang_contents.description',
        DB::raw('COALESCE(multilang_contents.detailed_description,"") as detailed_description'),
        'multilang_contents.page_title',
        'multilang_contents.meta_description',
        'multilang_contents.meta_keywords',
        'langs.full_name as lang',
        'langs.id as lang_id' ,
        'stocks.real_qty',
        'stocks.virtual_qty',
        DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
        DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
           ->join('multilang_contents', 'products.id', '=', 'multilang_contents.module_id')
           ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
           ->join('users as created_by', 'created_by.id', '=', 'products.created_by')
           ->leftJoin('users as updated_by', 'updated_by.id', '=', 'products.updated_by')
           ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
           ->leftJoin('product_categories as sub_categories', 'sub_categories.id', '=', 'products.product_category_id')
           ->leftJoin('multilang_contents as sub_categories_lang', 
           [
            'sub_categories_lang.lang_id' => 'langs.id',
            'sub_categories_lang.module_id' => 'sub_categories.id'
           ]
        )
           ->leftJoin('product_categories as categories', 'categories.id', '=', 'sub_categories.product_category_id')
           ->leftJoin('multilang_contents as categories_lang',[
            'categories_lang.lang_id' => 'langs.id',
            'categories_lang.module_id' => 'categories.id'
           ]
            )
           ->where(['multilang_contents.module'=> 'PRODUCT',
           'langs.id' => 1 ])
           ->where(function($query)
           {
               $query->where([
                'categories_lang.module' => 'PRODUCT_CATEGORY',
                'sub_categories_lang.module' => 'PRODUCT_CATEGORY',
                'products.type' => 'PRODUCT'
               ])
               ->orWhere('products.type','=','KIT');
           })
           ->orderBy('products.id', 'desc')
           ->get();

    }

    public function index2($is_visitor = false)
    {
        return Product::select('products.*',
        'multilang_contents.name as label',
        'multilang_contents.name',
        'multilang_contents.description',
        DB::raw('COALESCE(multilang_contents.detailed_description,"") as detailed_description'),
        'multilang_contents.page_title',
        'multilang_contents.meta_description',
        'multilang_contents.meta_keywords',
        'pricings.price',
        'langs.full_name as lang',
        'langs.id as lang_id' ,
        'stocks.real_qty',
        'stocks.virtual_qty')
           ->join('multilang_contents', 'products.id', '=', 'multilang_contents.module_id')
           ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
           ->join('pricings', 'pricings.module_id', '=', 'products.id')
           ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
           ->where([
                'multilang_contents.module'=> 'PRODUCT',
                'products.is_activated' => 1 ,
                'pricings.module' => 'PRODUCT',
                'pricings.customer_category_id' => $is_visitor ? 5 : Auth::user()->category_id
                ])
           ->orderBy('products.id', 'desc')
           ->get();

    }

    public function indexForVisitor(){
        $is_visitor = true;
        return $this->index2($is_visitor);
    }


    public function search(Request $request){

        $request->validate([
            //'lang_id' => 'required',
            'q' => 'required'
            ]);

        $a = DB::table('multilang_contents')
        ->select('multilang_contents.name',
         'multilang_contents.module_id',
         'multilang_contents.module',
          'brands.image')
        ->join('brands', 'brands.id', '=', 'multilang_contents.module_id')
        ->where('name', 'like', '%' . $request->q . '%')
        ->where('module', '=', 'BRAND')
        //->where('multilang_contents.lang_id', '=', $request->lang_id)
        ;

        $b = DB::table('multilang_contents')
        ->select('multilang_contents.name',
         'multilang_contents.module_id',
         'multilang_contents.module',
          'product_categories.image')
        ->join('product_categories', 'product_categories.id', '=', 'multilang_contents.module_id')
        ->where('name', 'like', '%' . $request->q . '%')
        ->where('module', '=', 'PRODUCT_CATEGORY')
        //->where('multilang_contents.lang_id', '=', $request->lang_id)
        ;


        return DB::table('multilang_contents')
        ->select('multilang_contents.name', 'multilang_contents.module_id', 'multilang_contents.module', 'products.default_image as image')
        ->join('products', 'products.id', '=', 'multilang_contents.module_id')
        ->where('name', 'like', '%' . $request->q . '%')
        ->where('module', '=', 'PRODUCT')
        //->where('multilang_contents.lang_id', '=', $request->lang_id)
        ->union($a)
        ->union($b)
        ->get();
    }

    public function getProductsByCategory($id, $visitor = false){

        return Product::select('products.*',
        'multilang_contents.name as label',
        'multilang_contents.name',
        'multilang_contents.description',
        DB::raw('COALESCE(multilang_contents.detailed_description,"") as detailed_description'),
        'multilang_contents.page_title',
        'multilang_contents.meta_description',
        'multilang_contents.meta_keywords',
        'pricings.price',
        'langs.full_name as lang',
        'langs.id as lang_id' )
           ->join('multilang_contents', 'products.id', '=', 'multilang_contents.module_id')
           ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
           ->join('pricings', 'pricings.module_id', '=', 'products.id')
           ->join('product_categories', 'products.product_category_id', '=', 'product_categories.id')
           ->where([
                'multilang_contents.module'=> 'PRODUCT',
                'products.is_activated' => 1 ,
                'pricings.module' => 'PRODUCT',
                'pricings.customer_category_id' => !$visitor ? Auth::user()->category_id : 5
                ])
            ->where(function($query) use ($id)
            {
            $query->where([
                 'product_categories.product_category_id' => $id
                ])
                ->orWhere('product_categories.id','=',$id);
            })
           ->orderBy('products.id', 'desc')
           ->get();

    }

    public function getProductsByCategoryForVisitor($id){
        $visitor = true;
        return $this->getProductsByCategory($id, $visitor);
    }


    public function getProductsByBrand($id, $visitor = false){

        return Product::select('products.*',
        'multilang_contents.name as label',
        'multilang_contents.name',
        'multilang_contents.description',
        DB::raw('COALESCE(multilang_contents.detailed_description,"") as detailed_description'),
        'multilang_contents.page_title',
        'multilang_contents.meta_description',
        'multilang_contents.meta_keywords',
        'pricings.price',
        'langs.full_name as lang',
        'langs.id as lang_id' )
           ->join('multilang_contents', 'products.id', '=', 'multilang_contents.module_id')
           ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
           ->join('pricings', 'pricings.module_id', '=', 'products.id')
           ->where([
                'multilang_contents.module'=> 'PRODUCT',
                'products.is_activated' => 1 ,
                'pricings.module' => 'PRODUCT',
                'products.brand_id' => $id,
                'pricings.customer_category_id' => !$visitor ? Auth::user()->category_id : 5
                ])
           ->orderBy('products.id', 'desc')
           ->get();

    }

    public function getProductsByBrandForVisitor($id){
        $visitor = true;
        return $this->getProductsByBrand($id, $visitor);
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



    public function loadProductFiles(Request $request)
    {
        $langs= Lang::all();
        $cols_count = null;
        $rows_count = null;
        $first_file = null;
        foreach($langs as $lang ){
            if($request->hasFile('file_' . $lang->id)){

                $xls_cols = Excel::toArray(new ExcelImport, $request->file('file_' . $lang->id));
                //Check columns number in all files ( must be the same )
                //Check rows number in all files ( must be the same too )
                if( ($cols_count !== null && count($xls_cols[0][0]) !== $cols_count ) ||
                ($rows_count !== null && count($xls_cols[0]) !== $rows_count )  ){
                    return response()->json(['err' => 'Le nombre de lignes et colonnes doit ètre le mème dans tous les fichiers'], 500);
                }
                $cols_count = count($xls_cols[0][0]);
                if($first_file === null) {
                    $first_file = $xls_cols ;
                }
            }
            else{
                return response()->json(['err' => 'Veuillez choisir un fichier pour chaque langue'], 500);
            }
        }

        return response()->json($first_file[0][0], 200);
    }

    public function importProductFiles(Request $request) {

        $request->validate([
            'force_importation' => 'required'
        ]);

        $product_fields = json_decode($request->product_fields, true);
        $attributes = json_decode($request->product_attributes, true);
        $pricing = json_decode($request->pricing, true);

        $langs= Lang::all();
        $cols_count = null;
        $rows_count = null;
        $first_index = null;

        foreach($langs as $lang ){
            if($request->hasFile('file_' . $lang->id)){

                $xls_cols = Excel::toArray(new ExcelImport, $request->file('file_' . $lang->id));
                //Check columns number in all files ( must be the same )
                //Check rows number in all files ( must be the same too )
                if( ($cols_count !== null && count($xls_cols[0][0]) !== $cols_count ) ||
                ($rows_count !== null && count($xls_cols[0]) !== $rows_count )  ){
                    return response()->json(['err' => 'Le nombre de lignes et colonnes doit ètre le mème dans tous les fichiers'], 500);
                }
                $cols_count = count($xls_cols[0][0]);
                if($first_index === null) {$first_index = $lang->id ;}
                $file = Excel::toArray(new ExcelImport, $request->file('file_' . $lang->id));
                $all_files[$lang->id] = $file[0];
            }
            else{
                return response()->json(['err' => 'Veuillez choisir un fichier pour chaque langue'], 500);
            }
        }

        if($request->force_importation === false){

            $exist = [];
            $first_file = $all_files[$first_index];

            for( $i=1; $i<count($first_file); $i++ ){

                if($all_files[$langs[0]->id][$i][0] !== null){

                    $code = $all_files[$langs[0]->id][$i][4];
                    $ref = $all_files[$langs[0]->id][$i][5];

                    $product = Product::where([
                        'code' => $code
                    ])
                    ->orWhere([
                        'reference' => $ref
                    ])
                    ->first();

                    if($product){
                        $msg = $product->code === $code ? 'Le code : ' . $code
                        : 'La réference : ' . $ref;
                        array_push( $exist, 
                            $msg . ' existe déja' 
                        );
                    }
                    
                }
            }

            if(count($exist) > 0){
                return response()->json(['exist_list' => $exist], 200);
            }

        }

        

        DB::transaction(function () use ($request, $product_fields, $attributes, $pricing, $langs, $first_index, $all_files ){
        
        if(count($all_files) > 0){

            $multilang_contents = array();
            $first_file = $all_files[$first_index];

            $category_id = null;
            $sub_category_id = null;
            $brand_id = null;
            $attribute_values = array();

            for( $i=1; $i<count($first_file); $i++ ){

                $product_id = null;
                if($all_files[$langs[0]->id][$i][0] !== null){
                for( $j=0; $j<count($langs); $j++ ){
                    if($j===0){
                        $category = DB::table('product_categories')
                        ->select('product_categories.id')
                        ->join('multilang_contents', 'multilang_contents.module_id', 'product_categories.id')
                        ->where(array(
                            'module' => 'PRODUCT_CATEGORY',
                            'name'   => $all_files[$langs[$j]->id][$i][0],
                            'lang_id'=> $langs[$j]->id
                        ))
                        ->first();

                        if(!$category){
                            $category = new ProductCategory;
                            $category->is_activated = 1;
                            $category->created_by = Auth::id();
                            $category->updated_by = Auth::id();
                            $category->save();

                            $category_id = $category->id;

                            $sub_category = new ProductCategory;
                            $sub_category->is_activated = 1;
                            $sub_category->created_by = Auth::id();
                            $sub_category->updated_by = Auth::id();
                            $sub_category->product_category_id = $category->id;
                            $sub_category->save();

                            $sub_category_id = $sub_category->id;
                        }
                        else{
                            $category_id = null;
                        }

                        if(!isset($sub_category)){

                            $sub_category = DB::table('product_categories')
                            ->select('product_categories.id')
                            ->join('multilang_contents', 'multilang_contents.module_id', 'product_categories.id')
                            ->join('product_categories as parent', 'parent.id', 'product_categories.product_category_id')
                            ->where(array(
                                'module' => 'PRODUCT_CATEGORY',
                                'name'   => $all_files[$langs[$j]->id][$i][1],
                                'lang_id'=> $langs[$j]->id,
                                'parent.id' => $category->id
                            ))
                            ->first();
                            
                            if(!$sub_category){
                                $sub_category = new ProductCategory;
                                $sub_category->is_activated = 1;
                                $sub_category->created_by = Auth::id();
                                $sub_category->updated_by = Auth::id();
                                $sub_category->product_category_id = $category->id;
                                $sub_category->save();

                                $sub_category_id = $sub_category->id;

                            }
                            else{
                                $sub_category_id = null;
                            }
                            
                        }

                        
                        $brand = DB::table('brands')
                        ->select('brands.id')
                        ->join('multilang_contents', 'multilang_contents.module_id', 'brands.id')
                        ->where(array(
                            'module' => 'BRAND',
                            'name'   => $all_files[$langs[$j]->id][$i][3],
                            'lang_id'=> $langs[$j]->id
                        ))
                        ->first();

                        if(!$brand){
                            $brand = new Brand;
                            $brand->is_activated = 1;
                            $brand->created_by = Auth::id();
                            $brand->updated_by = Auth::id();
                            $brand->save();

                            $brand_id = $brand->id;
                        }
                        else{
                            $brand_id = null;
                        }

                        $product = new Product;
                        $product->is_activated = 1;
                        $product->type= 'PRODUCT';
                        $product->brand_id = $brand->id;
                        $product->product_category_id = $sub_category->id;
                        $product->guarantee = 1;
                        //$product->authtorize_reviews = 1;
                        $product->code = $all_files[$langs[$j]->id][$i][4];
                        $product->reference = $all_files[$langs[$j]->id][$i][5];
                        $product->bar_code = $all_files[$langs[$j]->id][$i][6];
                        $product->created_by = Auth::id();
                        $product->updated_by = Auth::id();
                        $product->sell_by_unit = 1;
                        $product->save();

                        $product_id = $product->id;

                        $attribute_id = 1;
                        
                        $product_values = [];
                        for($attribute_col_index = 9; $attribute_col_index<19; $attribute_col_index++){
                            
                            $v = $all_files[$langs[$j]->id][$i][$attribute_col_index];
                            $v_without_spaces = str_replace(' ', '', $v);
                            if(!empty($v) &&  $v_without_spaces !== '/'){
                                $attribute_value = new AttributeValue;
                                $attribute_value->attribute_id = $attribute_id;
                                $attribute_value->save();

                                array_push( $attribute_values, array(
                                    'id' => $attribute_value->id,
                                    'col_index' => $attribute_col_index,
                                ));
                                
                                array_push($product_values, [
                                    'product_id' => $product_id,
                                    'attribute_value_id' => $attribute_value->id
                                ]);
                            }

                            $attribute_id++;
                        }

                        DB::table('product_values')->insert($product_values);

  
                        $customer_categroy_id = 1;
                        for($price_col_index = 20 ; $price_col_index < 25; $price_col_index++) {
                            
                            $price = !empty($all_files[$langs[$j]->id][$i][$price_col_index]) ? $all_files[$langs[$j]->id][$i][$price_col_index] : null;
                            
                            if(!empty($price)){

                                $pricing = new Pricing;
                                $pricing->module_id = $product_id;
                                $pricing->module = 'PRODUCT';
                                $pricing->customer_category_id = $customer_categroy_id;
                                $pricing->price = is_numeric($price) ? $price : 0;
                                $pricing->save();
                            }
                            $customer_categroy_id++;
                        }
                    
                    }

                    foreach($attribute_values as $attribute_value) {

                        array_push($multilang_contents, 
                        array( 'module' => 'ATTRIBUTE_VALUE',
                                'module_id' => $attribute_value['id'],
                                'name' =>  $all_files[$langs[$j]->id][$i][$attribute_value['col_index']],
                                'lang_id' => $langs[$j]->id,
                                'description' => ''));

                    }

                    if($category_id){
                        array_push($multilang_contents, 
                        array( 'module' => 'PRODUCT_CATEGORY',
                            'module_id' => $category_id,
                                'name' =>  $all_files[$langs[$j]->id][$i][0],
                                'lang_id' => $langs[$j]->id,
                                'description' => ''));
                    }

                    if($sub_category_id){
                        array_push($multilang_contents, 
                        array( 'module' => 'PRODUCT_CATEGORY',
                            'module_id' => $sub_category_id,
                                'name' =>  $all_files[$langs[$j]->id][$i][1],
                                'lang_id' => $langs[$j]->id,
                                'description' => ''));
                    }

                    if($brand_id){
                        array_push($multilang_contents, 
                        array( 'module' => 'BRAND',
                            'module_id' => $brand_id,
                                'name' =>  $all_files[$langs[$j]->id][$i][3],
                                'lang_id' => $langs[$j]->id,
                                'description' => ''));
                    }

                    array_push($multilang_contents, 
                    array( 'module' => 'PRODUCT',
                            'module_id' => $product_id,
                                'name' =>  $all_files[$langs[$j]->id][$i][2],
                                'lang_id' => $langs[$j]->id,
                                'description' => $all_files[$langs[$j]->id][$i][7]));

                }
            }
            }

            DB::table('multilang_contents')->insert($multilang_contents);

        }
        
    });
    }

    public function store(Request $request)
    {
            $request->validate([
                'is_activated' => 'required',
                'is_guarantee' => 'required_if:type,==,PRODUCT',
                'sell_by_unit' => 'required_if:type,==,PRODUCT',
                'code' => 'required_if:type,==,PRODUCT',
                'bar_code' => 'required_if:type,==,PRODUCT',
                'reference' => 'required_if:type,==,PRODUCT',
                'alert_qty' => 'numeric',
                'brand_id' => 'required_if:type,==,PRODUCT',
                'product_category_id' => 'required_if:type,==,PRODUCT',
                'rows.*.name' => 'required',
                'image' => ['mimes:jpeg,jpg,png', 'max:10000'],
                //'images' => ['mimes:jpeg,jpg,png', 'max:10000'],
                'pricing.*.price' => 'numeric',
                ]);
    
                $product = new Product;
    
                $product->is_activated = $request->is_activated;
                $product->type = $request->type;
                $product->created_by = Auth::id();
                $product->updated_by = Auth::id();
    
                if (!empty($request->image)) {
                    $imageName = time() . '.' . $request->image->getClientOriginalExtension();
                    $product->default_image = $imageName;
                    $request->image->move(public_path('images'), $imageName);
    
                }
                $product->images = '';

                if (!empty($request->extra_images_count)) {

                    for( $i=0; $i < $request->extra_images_count; $i++){
                        $imageName = time() . $i . '.' . $request['extra_image_' .$i]->getClientOriginalExtension();
                        $product->images.= ',' . $imageName;
                        $request['extra_image_' .$i]->move(public_path('images'), $imageName);
                    }

                }

                if($request->type === 'PRODUCT'){
                    $product->product_category_id = $request->product_category_id;
                    $product->brand_id = $request->brand_id;
                    $product->bar_code = $request->bar_code;
                    $product->code = $request->code;
                    $product->reference = $request->reference;
                    $product->guarantee = $request->is_guarantee;
                    $product->sell_by_unit = $request->sell_by_unit;
                    if(isset($request->alert_qty)){
                        $product->alert_qty = $request->alert_qty;
                    }
                }
                $product->note = $request->note;


                DB::transaction(function () use ($request, $product ) {
    
                    $product->save();
    
                    $data = [];
                    $pricing = [];
                    $selected_values = [];
                    $packages = [];
                    $kit_content = [];

                    foreach (json_decode($request->rows, true) as $key => $row) {
    
                        array_push($data, [
                            'name' => $row['name'],
                            'description' => $row['description'],
                            'detailed_description' => $row['detailed_description'],
                            'page_title' => $row['page_title'],
                            'meta_description' => $row['meta_description'],
                            'meta_keywords' => $row['meta_keywords'],
                            'lang_id' => $row['lang_id'],
                            'module' => 'PRODUCT',
                            'module_id' => $product->id
                        ]);
                    }
                    

                    foreach (json_decode($request->pricing, true) as $key => $row) {
    
                        array_push($pricing, [
                            'price' => $row['price'],
                            'module' => 'PRODUCT',
                            'module_id' => $product->id,
                            'customer_category_id' => $row['id'],
                        ]);
                    }
                    
                    if($request->type === 'PRODUCT'){
                        
                    foreach (json_decode($request->packages, true) as $key => $row) {
                        
                        if($row[0]['is_default']){
                            DB::table('product_packges')->where([
                                'product_id' => $product->id
                            ])
                            ->update([
                                'is_default' => 0
                            ]);
                        }

                        $productPackage = new ProductPackge;
                        $productPackage->qty = $row[0]['qty'];
                        $productPackage->is_activated = $row[0]['is_activated'] ? 1 : 0;
                        $productPackage->product_id = $product->id;
                        $productPackage->is_default = $row[0]['is_default'] ? 1 : 0;
                        $productPackage->customer_category_id = $row[0]['customer_category_id'];
                        $productPackage->save();

                        // foreach ($row[0]['pricing'] as $key => $p) {

                        //     $a  = [
                        //         'price' => $p['price'],
                        //         'module' => 'PRODUCT_PACKAGE',
                        //         'module_id' => $productPackage->id,
                        //         'customer_category_id' => $p['id']
                        //     ];

                        //     array_push($pricing, $a);
                        // }
                        
                        foreach($row as $r){
                            array_push($packages, [
                                'name' => $r['name'],
                                'description' => '',
                                'detailed_description' => '',
                                'page_title' => '',
                                'meta_description' => '',
                                'meta_keywords' => '',
                                'lang_id' => $r['lang_id'],
                                'module' => 'PRODUCT_PACKAGE',
                                'module_id' => $productPackage->id
                            ]);

                        }
                    }

                    foreach (json_decode($request->selected_values, true) as $row) {
    
                        array_push($selected_values, [
                            'attribute_value_id' => $row,
                            'product_id' => $product->id,
                        ]);
                    }
                    DB::table('product_values')->insert($selected_values);
                    DB::table('multilang_contents')->insert($packages);
                }
                elseif( $request->type === 'KIT'){

                    foreach (json_decode($request->kit_content, true) as $key => $row) {

                        array_push($kit_content, [
                            'product_id' => $row['product_id'],
                            'kit_id' => $product->id,
                            'qty' => $row['qty']
                        ]);
                        
                    }

                    DB::table('kit_details')->insert($kit_content);
                }

                    DB::table('pricings')->insert($pricing);
                    DB::table('multilang_contents')->insert($data);
    
                });
                $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
                $product->created_by = $auth_user_full_name;
                $product->updated_by = $auth_user_full_name;
                return response()->json($product, 200);
        
    }

    public function duplicateProduct($id)
    {
                $old = Product::findOrFail($id);
                $product = new Product;
                $product->type = 'PRODUCT';
                $product->is_activated = $old->is_activated;
                $product->created_by = Auth::id();
                $product->updated_by = Auth::id();
                $product->default_image = $old->default_image;
                $product->sell_by_unit = $old->sell_by_unit;
                $product->images = $old->images;

                $product->product_category_id = $old->product_category_id;
                $product->brand_id = $old->brand_id;
                $product->bar_code = $old->bar_code;
                $product->code = $old->code;
                $product->reference = $old->reference;
                $product->guarantee = $old->guarantee;
                $product->note = $old->note;
                $product->alert_qty = $old->alert_qty;
                
    
                DB::transaction(function () use ( $old, $product) {
    
                    $product->save();
    
                    $rows = DB::table('multilang_contents')->where(['module' => 'PRODUCT', 'module_id' => $old->id])->get();
                    $data = [];
                    $pricing_old = Pricing::where(['module' => 'PRODUCT', 'module_id' => $old->id])->get();
                    $pricing = [];
                    $product_values_old = DB::table('product_values')->where('product_id' , '=', $old->id)->get();
                    $product_values = [];

                    foreach ($rows as $key => $row) {
    
                        array_push($data, [
                            'name' => $row->name,
                            'description' => $row->description,
                            'detailed_description' => $row->detailed_description,
                            'page_title' => $row->page_title,
                            'meta_description' => $row->meta_description,
                            'meta_keywords' => $row->meta_keywords,
                            'lang_id' => $row->lang_id,
                            'module' => 'PRODUCT',
                            'module_id' => $product->id
                        ]);
                    }

                    foreach ($pricing_old as $key => $row) {
    
                        array_push($pricing, [
                            'price' => $row->price,
                            'module' => 'PRODUCT',
                            'module_id' => $product->id,
                            'customer_category_id' => $row->customer_category_id,
                        ]);
                    }

                    foreach ($product_values_old as $row) {
    
                        array_push($product_values, [
                            'attribute_value_id' => $row->attribute_value_id,
                            'product_id' => $product->id,
                        ]);
                    }
    
                    DB::table('pricings')->insert($pricing);
                    DB::table('product_values')->insert($product_values);
                    DB::table('multilang_contents')->insert($data);
                    
    
                });
                $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
                $product->created_by = $auth_user_full_name;
                $product->updated_by = $auth_user_full_name;
                return response()->json($product, 200);
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $product = Product::select('products.*', 'product_categories.product_category_id as category_id',   
        'categories_lang.name as category_name',
        'sub_categories_lang.name as sub_category_name',
        'brands_lang.name as brand',
        DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
        DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
        ->leftJoin('product_categories', 'product_categories.id', '=', 'products.product_category_id')
        ->join('users as created_by', 'created_by.id', '=', 'products.created_by')
        ->leftJoin('users as updated_by', 'updated_by.id', '=', 'products.updated_by')
        ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
        ->leftJoin('multilang_contents as brands_lang', 
        [
         'brands_lang.module_id' => 'brands.id'
        ]
     )
        ->leftJoin('product_categories as sub_categories', 'sub_categories.id', '=', 'products.product_category_id')
        ->leftJoin('multilang_contents as sub_categories_lang', 
        [
         'sub_categories_lang.module_id' => 'sub_categories.id'
        ]
     )
        ->leftJoin('product_categories as categories', 'categories.id', '=', 'sub_categories.product_category_id')
        ->leftJoin('multilang_contents as categories_lang',[
         'categories_lang.lang_id' => 'sub_categories_lang.lang_id',
         'categories_lang.module_id' => 'categories.id'
        ]
         )
        ->where(['products.id' => $id])
        ->where(function($query)
        {
        $query->where([
             'categories_lang.module' => 'PRODUCT_CATEGORY',
             'sub_categories_lang.module' => 'PRODUCT_CATEGORY',
             'brands_lang.lang_id' => 1,
             'products.type' => 'PRODUCT',
             'sub_categories_lang.lang_id' => 1
            ])
            ->orWhere('products.type','=','KIT');
        })
        ->first();

        $product->fields = DB::table('multilang_contents')
        ->where([
            'module' => 'PRODUCT',
            'module_id' => $id
        ])->get();
        
        if($product->type === 'PRODUCT'){

            $product->productPackages = DB::table('product_packges')
            ->select('product_packges.*', 'multilang_contents.name')
            ->join('multilang_contents', 'multilang_contents.module_id', '=', 'product_packges.id')
            ->where([
                'multilang_contents.module' => 'PRODUCT_PACKAGE',
                'product_packges.product_id'     => $id ,
                'lang_id' => 1
            ])
            ->get();

            $product->productValues = DB::table('product_values')
            ->select('product_values.*', 'attribute_values.attribute_id', 'attribute_langs.name as attribute', 'multilang_contents.name')
            ->join('attribute_values', 'attribute_values.id', '=', 'product_values.attribute_value_id')
            ->join('multilang_contents', 'multilang_contents.module_id', '=', 'product_values.attribute_value_id')
            ->join('multilang_contents as attribute_langs', 'attribute_langs.module_id', '=', 'attribute_values.attribute_id')
            ->where([
                'multilang_contents.module'     => 'ATTRIBUTE_VALUE',
                'attribute_langs.module'     => 'ATTRIBUTE',
                'product_values.product_id'     => $id ,
                'multilang_contents.lang_id' => 1,
                'attribute_langs.lang_id' => 1
            ])
            ->get();
        }
        elseif($product->type === 'KIT'){

            $product->kitContent = DB::table('kit_details')
            ->select('kit_details.*', 'products.code', 'multilang_contents.name')
            ->join('products', 'products.id', '=', 'kit_details.product_id')
            ->join('multilang_contents', 'multilang_contents.module_id', '=', 'kit_details.product_id')
            ->where([
                'multilang_contents.module'     => 'PRODUCT',
                'kit_details.kit_id'     => $id ,
                'multilang_contents.lang_id' => 1,
            ])
            ->get();

        }


        $product->pricing = DB::table('pricings')
        ->select('pricings.*')
        ->where([
            'pricings.module' => 'PRODUCT', 
            'pricings.module_id' => $id ,
        ])
        ->get();

        return response()->json($product, 200);
    }

    public function show2($id, $visitor = false)
    {

        $product = Product::select('products.*', 'product_categories.product_category_id as category_id',   
        'categories_lang.name as category_name',
        'sub_categories_lang.name as sub_category_name',
        'pricings.price',
        'brands_lang.name as brand',
        'stocks.real_qty')
        ->leftJoin('product_categories', 'product_categories.id', '=', 'products.product_category_id')
        ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
        ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
        ->leftJoin('multilang_contents as brands_lang', 
        [
         'brands_lang.module_id' => 'brands.id'
        ]
     )
        ->leftJoin('product_categories as sub_categories', 'sub_categories.id', '=', 'products.product_category_id')
        ->leftJoin('multilang_contents as sub_categories_lang', 
        [
         'sub_categories_lang.module_id' => 'sub_categories.id'
        ]
     )
        ->leftJoin('product_categories as categories', 'categories.id', '=', 'sub_categories.product_category_id')
        ->leftJoin('multilang_contents as categories_lang',[
         'categories_lang.lang_id' => 'sub_categories_lang.lang_id',
         'categories_lang.module_id' => 'categories.id'
        ]
         )
         ->join('pricings', 'pricings.module_id', '=', 'products.id')
         ->where([
            'products.id' => $id,
            'products.is_activated' => 1 ,
            'pricings.module' => 'PRODUCT',
            'pricings.customer_category_id' => !$visitor ? Auth::user()->category_id : 5
            ])
        ->where(function($query)
        {
        $query->where([
             'categories_lang.module' => 'PRODUCT_CATEGORY',
             'sub_categories_lang.module' => 'PRODUCT_CATEGORY',
             //'brands_lang.lang_id' => 1,
             'products.type' => 'PRODUCT',
             //'sub_categories_lang.lang_id' => 1
            ])
            ->orWhere('products.type','=','KIT');
        })

        ->first();

        $product->fields = DB::table('multilang_contents')
        ->where([
            'module' => 'PRODUCT',
            'module_id' => $id
        ])->get();

        $product->rating = DB::table('product_ratings')
        ->select('rating', DB::raw('count(*) as rating_count'))
        ->where([
            'product_id' => $id
        ])
        ->groupBy('rating')
        ->get();

        $product->notices = DB::table('customer_notices')
        ->join('customers', 'customers.id', '=', 'customer_notices.customer_id')
        ->select('customers.first_name', 'customers.last_name', 'customers.image', 'notice', 'customer_notices.created_at')
        ->where([
            'customer_notices.product_id' => $id,
            'customer_notices.is_approved' => 1
        ])
        ->get();
        
        if($product->type === 'PRODUCT'){

            $product->productPackages = DB::table('product_packges')
            ->select('product_packges.*', 'multilang_contents.name', 'pricings.price', 'multilang_contents.lang_id')
            ->join('multilang_contents', 'multilang_contents.module_id', '=', 'product_packges.id')
            ->join('pricings', 'pricings.customer_category_id', '=', 'product_packges.customer_category_id')
            ->where([
                'multilang_contents.module' => 'PRODUCT_PACKAGE',
                'product_packges.product_id' => $id,
                'pricings.module' => 'PRODUCT',
                'pricings.module_id' => $id,
                'product_packges.is_activated' => 1,
                //'pricings.customer_category_id' => 'product_packges.customer_category_id'
                //'lang_id' => 1
            ])
            ->get();

            $product->productValues = DB::table('product_values')
            ->select('product_values.*',
             'attribute_values.attribute_id', 
             'attribute_langs.name as attribute',
              'multilang_contents.name',
              'multilang_contents.lang_id')
            ->join('attribute_values', 'attribute_values.id', '=', 'product_values.attribute_value_id')
            ->join('multilang_contents', 'multilang_contents.module_id', '=', 'product_values.attribute_value_id')
            ->join('multilang_contents as attribute_langs', 
            ['attribute_langs.module_id' => 'attribute_values.attribute_id',
            'attribute_langs.lang_id' => 'multilang_contents.lang_id' ])
            ->where([
                'multilang_contents.module'     => 'ATTRIBUTE_VALUE',
                'attribute_langs.module'     => 'ATTRIBUTE',
                'product_values.product_id'     => $id ,
                //'multilang_contents.lang_id' => 1,
                //'attribute_langs.lang_id' => 1
            ])
            ->get();
        }
        elseif($product->type === 'KIT'){

            $product->kitContent = DB::table('kit_details')
            ->select('kit_details.*', 'products.code', 'multilang_contents.name', 'multilang_contents.lang_id')
            ->join('products', 'products.id', '=', 'kit_details.product_id')
            ->join('multilang_contents', 'multilang_contents.module_id', '=', 'kit_details.product_id')
            ->where([
                'multilang_contents.module'     => 'PRODUCT',
                'kit_details.kit_id'     => $id ,
                //'multilang_contents.lang_id' => 1,
            ])
            ->get();

        }

        return response()->json($product, 200);
    }

    public function showForVisitor($id){
        $visitor = true;
        return $this->show2($id, $visitor);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if(!empty($request->delete_image)){

            $product = Product::findOrFail($id);
            //$image_name = !empty($request->image_name) ? $request->image_name : $product->image;
            
            //File::delete(public_path('images/' . $image_name));
            
            if(empty($request->image_name)){
                $product->default_image = null;
            }
            else{
                $product->images = str_replace($image_name, '', $product->images);
            }
            
            $product->save();
            
            return response()->json([], 200);
        }
        else{

            $request->validate([
                'is_activated' => 'required',
                'is_guarantee' => 'required_if:type,==,PRODUCT',
                'sell_by_unit' => 'required_if:type,==,PRODUCT',
                'code' => 'required_if:type,==,PRODUCT',
                'bar_code' => 'required_if:type,==,PRODUCT',
                'reference' => 'required_if:type,==,PRODUCT',
                'alert_qty' => 'numeric',
                'brand_id' => 'required_if:type,==,PRODUCT',
                'product_category_id' => 'required_if:type,==,PRODUCT',
                'rows.*.name' => 'required',
                'image' => ['mimes:jpeg,jpg,png', 'max:10000'],
                //'images' => ['mimes:jpeg,jpg,png', 'max:10000'],
                'pricing.*.price' => 'numeric',
                ]);
    
                $product = Product::findOrFail($id);
    
                $product->is_activated = $request->is_activated;
                $product->updated_by = Auth::id();
    
                if (!empty($request->image)) {

                    if(file_exists(public_path('images/' . $product->default_image))){
                        File::delete(public_path('images/' . $product->default_image));
                    }

                    $imageName = time() . '.' . $request->image->getClientOriginalExtension();
                    $product->default_image = $imageName;
                    $request->image->move(public_path('images'), $imageName);
    
                }

                if (!empty($request->extra_images_count)) {

                    for( $i=0; $i < $request->extra_images_count; $i++){
                        $imageName = time() . $i . '.' . $request['extra_image_' .$i]->getClientOriginalExtension();
                        $product->images.= ',' . $imageName;
                        $request['extra_image_' .$i]->move(public_path('images'), $imageName);
                    }

                }

                if($request->type === 'PRODUCT') {
                    $product->product_category_id = $request->product_category_id;
                    $product->brand_id = $request->brand_id;
                    $product->bar_code = $request->bar_code;
                    $product->code = $request->code;
                    $product->reference = $request->reference;
                    $product->guarantee = $request->is_guarantee;
                    $product->sell_by_unit = $request->sell_by_unit;

                    if(isset($request->alert_qty)){
                        $product->alert_qty = $request->alert_qty;
                    }
                }

                $product->note = $request->note;
    
                DB::transaction(function () use ($request, $product, $id) {
    
                    $product->save();
                    $packages = [];
                    $data = [];
                    $pricing = [];
                    $selected_values = [];
                    
                    foreach (json_decode($request->rows, true) as $key => $row) {
    
                        array_push($data, [
                            'name' => $row['name'],
                            'description' => $row['description'],
                            'detailed_description' => $row['detailed_description'],
                            'page_title' => $row['page_title'],
                            'meta_description' => $row['meta_description'],
                            'meta_keywords' => $row['meta_keywords'],
                            'lang_id' => $row['lang_id'],
                            'module' => 'PRODUCT',
                            'module_id' => $product->id
                        ]);
                    }

                    foreach (json_decode($request->pricing, true) as $key => $row) {
    
                        array_push($pricing, [
                            'price' => $row['price'],
                            'module' => 'PRODUCT',
                            'module_id' => $product->id,
                            'customer_category_id' => $row['id'],
                        ]);
                    }

                    if($request->type==='PRODUCT'){

                        foreach (json_decode($request->selected_values, true) as $row) {
    
                            array_push($selected_values, [
                                'attribute_value_id' => $row,
                                'product_id' => $product->id,
                            ]);
                        }

                        foreach (json_decode($request->packages, true) as $key => $row) {

                            if(!isset($row[0]['id'])){
                            $productPackage = new ProductPackge;
                            $productPackage->qty = $row[0]['qty'];
                            $productPackage->is_activated = $row[0]['is_activated'] ? 1 : 0;
                            $productPackage->product_id = $product->id;
                            $productPackage->is_default_package = $row[0]['is_default'] ? 1 : 0;
                            $productPackage->customer_category_id = $row[0]['customer_category_id'];
                            $productPackage->save();
        
                        }

                        foreach($row as $r){
                            array_push($packages, [
                                'name' => $r['name'],
                                'description' => '',
                                'detailed_description' => '',
                                'page_title' => '',
                                'meta_description' => '',
                                'meta_keywords' => '',
                                'lang_id' => $r['lang_id'],
                                'module' => 'PRODUCT_PACKAGE',
                                'module_id' => $productPackage->id
                            ]);

                        }

                    }

                    DB::table('product_values')->where(['product_id' => $id])->delete();
                    DB::table('product_values')->insert($selected_values);
                    DB::table('multilang_contents')->insert($packages);

                    }

                    DB::table('multilang_contents')->where(['module_id' => $id, 'module' => 'PRODUCT'])->delete();
                    DB::table('pricings')->where(['module_id' => $id, 'module' => 'PRODUCT'])->delete();
                    DB::table('pricings')->insert($pricing);
                    DB::table('multilang_contents')->insert($data);
                    
                });

                $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
                $product->created_by = $auth_user_full_name;
                $product->updated_by = $auth_user_full_name;
                return response()->json($product, 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
