<?php

namespace App\Http\Controllers;

use App\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Stock::select('stocks.*',
        'product_multilang_contents.name',
        'products.code',
        'category_multilang_contents.name as category_name',
        'products.alert_qty',
        'langs.full_name as lang',
        'langs.id as lang_id' )
        ->join('products', 'products.id', '=', 'stocks.product_id')
        ->join('multilang_contents as product_multilang_contents', 'products.id', '=', 'product_multilang_contents.module_id')
        ->join('product_categories', 'product_categories.id', '=', 'products.product_category_id')
        ->join('multilang_contents as category_multilang_contents', 'product_categories.id', '=', 'category_multilang_contents.module_id')
        ->join('langs', [
            'product_multilang_contents.lang_id' => 'langs.id',
            'category_multilang_contents.lang_id' => 'langs.id'
        ])
           ->where([
            'product_multilang_contents.module' => 'PRODUCT',
            'category_multilang_contents.module' => 'PRODUCT_CATEGORY',
            'langs.id' => 1
           ])
           ->orderBy('stocks.id', 'desc')
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
     * @param  \App\Stock  $stock
     * @return \Illuminate\Http\Response
     */
    public function show(Stock $stock)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Stock  $stock
     * @return \Illuminate\Http\Response
     */
    public function edit(Stock $stock)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Stock  $stock
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Stock $stock)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Stock  $stock
     * @return \Illuminate\Http\Response
     */
    public function destroy(Stock $stock)
    {
        //
    }
}
