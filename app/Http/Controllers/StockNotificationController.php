<?php

namespace App\Http\Controllers;

use App\StockNotification;
use Illuminate\Http\Request;
use Auth;

class StockNotificationController extends Controller
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
        //
    }

    public function alertWhenProductArrives($id){

        $notification = new StockNotification;
        $notification->customer_id = Auth::id();
        $notification->product_id = $id;
        $notification->closed = 0;
        $notification->save();

        return response()->json('', 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\StockNotification  $stockNotification
     * @return \Illuminate\Http\Response
     */
    public function show(StockNotification $stockNotification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\StockNotification  $stockNotification
     * @return \Illuminate\Http\Response
     */
    public function edit(StockNotification $stockNotification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\StockNotification  $stockNotification
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, StockNotification $stockNotification)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\StockNotification  $stockNotification
     * @return \Illuminate\Http\Response
     */
    public function destroy(StockNotification $stockNotification)
    {
        //
    }
}
