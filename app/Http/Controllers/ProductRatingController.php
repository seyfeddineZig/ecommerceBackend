<?php

namespace App\Http\Controllers;

use App\ProductRating;
use Illuminate\Http\Request;
use Auth;

class ProductRatingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
            'product_id' => 'required',
            'rating' => 'required'
            ]);

        $productRating = ProductRating::where([
            'customer_id' => Auth::id(),
            'product_id' => $request->product_id
        ])->first();

        if(!$productRating){
            $productRating = new ProductRating;
            $productRating->customer_id = Auth::id();
            $productRating->product_id = $request->product_id;
        }

        $productRating->rating = $request->rating;
        $productRating->save();

        return response()->json('', 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\ProductRating  $productRating
     * @return \Illuminate\Http\Response
     */
    public function show(ProductRating $productRating)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ProductRating  $productRating
     * @return \Illuminate\Http\Response
     */
    public function edit(ProductRating $productRating)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\ProductRating  $productRating
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProductRating $productRating)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ProductRating  $productRating
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProductRating $productRating)
    {
        //
    }
}
