<?php

namespace App\Http\Controllers;

use App\ProductPackge;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProductPackageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return ProductPackge::select('product_packges.*',
        'multilang_contents.name as label',
        'multilang_contents.name',
        'langs.full_name as lang',
        'langs.id as lang_id' )
           ->join('multilang_contents', 'product_packges.id', '=', 'multilang_contents.module_id')
           ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
           ->where('multilang_contents.module', 'PRODUCT_PACKAGE')
           ->where('langs.id', 1)
           ->orderBy('product_packges.id', 'desc')
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $packages = ProductPackge::findOrFail($id);

        $packages->fields = DB::table('multilang_contents')
        ->select('multilang_contents.name as label',
        'multilang_contents.name',
        'langs.full_name as lang',
        'langs.id as lang_id' )
           ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
           ->where([
            'multilang_contents.module' => 'PRODUCT_PACKAGE',
            'multilang_contents.module_id' => $id
           ])
           ->get();

        return response()->json($packages, 200);

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
            'is_activated' => 'required',
            'rows.*.name' => 'required',
            'rows.*.lang_id' => 'required',
            'is_default' => 'required',
            'customer_category_id' => 'required'
            ]);

            DB::transaction(function () use ($request, $id ) {

                $package = ProductPackge::findOrFail($id);
                $package->is_activated = $request->is_activated ? 1 : 0;
                $package->is_default_package = $request->is_default ? 1 : 0;
                $package->customer_category_id = $request->customer_category_id;

                if($package->is_default){
                    DB::table('product_packges')->where([
                        'product_id' => $package->product_id
                    ])
                    ->update([
                        'is_default_package' => 0
                    ]);
                }
                
                $package->save();

                foreach ($request->rows as $key => $row) {

                    DB::table('multilang_contents')->where([
                        'module' => 'PRODUCT_PACKAGE',
                        'module_id' => $id,
                        'lang_id' => $row['lang_id']
                    ])
                    ->update([
                        'name' => $row['name']
                    ]);

                }
                
            });
            return response()->json([], 200);
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
}
