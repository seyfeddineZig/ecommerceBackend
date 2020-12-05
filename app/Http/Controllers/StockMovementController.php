<?php

namespace App\Http\Controllers;

use App\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return StockMovement::select('stock_movements.*',
        'product_multilang_contents.name',
        'products.code',
        'category_multilang_contents.name as category_name',
        'langs.full_name as lang',
        'langs.id as lang_id' )
        ->join('products', ['products.id' =>  'stock_movements.module_id'])
        ->join('multilang_contents as product_multilang_contents', 'products.id', '=', 'product_multilang_contents.module_id')
        ->join('product_categories', 'product_categories.id', '=', 'products.product_category_id')
        ->join('multilang_contents as category_multilang_contents', 'product_categories.id', '=', 'category_multilang_contents.module_id')
        ->join('langs', [
            'product_multilang_contents.lang_id' => 'langs.id',
            'category_multilang_contents.lang_id' => 'langs.id'
        ])
           ->where([
            'stock_movements.module' => 'PRODUCT',
            'product_multilang_contents.module' => 'PRODUCT',
            'category_multilang_contents.module' => 'PRODUCT_CATEGORY',
            'langs.id' => 1
           ])
           ->orderBy('stock_movements.id', 'desc')
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
     * @param  \App\StockMovement  $stockMovement
     * @return \Illuminate\Http\Response
     */
    public function show(StockMovement $stockMovement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\StockMovement  $stockMovement
     * @return \Illuminate\Http\Response
     */
    public function edit(StockMovement $stockMovement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\StockMovement  $stockMovement
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, StockMovement $stockMovement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\StockMovement  $stockMovement
     * @return \Illuminate\Http\Response
     */
    public function destroy(StockMovement $stockMovement)
    {
        //
    }
}
