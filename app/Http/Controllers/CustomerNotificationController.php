<?php

namespace App\Http\Controllers;

use App\CustomerNotification;
use Illuminate\Http\Request;
use Auth;

class CustomerNotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return CustomerNotification::select('customer_notifications.*',
         'multilang_contents.name', 'multilang_contents.lang_id', 'products.default_image')
        ->leftJoin('products', 'products.id', '=', 'customer_notifications.product_id')
        ->leftJoin('multilang_contents', 'multilang_contents.module_id', '=', 'customer_notifications.product_id')
        ->where([
            'multilang_contents.module' => 'PRODUCT',
            'customer_notifications.customer_id' => Auth::id()
        ])
        ->orWhere([
            'customer_notifications.customer_id' => Auth::id()
        ])
        ->get();
    }

    public function markAllNotificationsAsSeen() {
        
        CustomerNotification::where(['customer_id' => Auth::id() ])->update(['seen' => 1]);
        return response()->json('', 200);
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
     * @param  \App\CustomerNotification  $customerNotification
     * @return \Illuminate\Http\Response
     */
    public function show(CustomerNotification $customerNotification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\CustomerNotification  $customerNotification
     * @return \Illuminate\Http\Response
     */
    public function edit(CustomerNotification $customerNotification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\CustomerNotification  $customerNotification
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CustomerNotification $customerNotification)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\CustomerNotification  $customerNotification
     * @return \Illuminate\Http\Response
     */
    public function destroy(CustomerNotification $customerNotification)
    {
        //
    }
}
