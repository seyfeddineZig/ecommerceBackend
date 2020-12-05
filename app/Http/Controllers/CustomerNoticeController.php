<?php

namespace App\Http\Controllers;

use App\CustomerNotice;
use Illuminate\Http\Request;
use Auth;

class CustomerNoticeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return CustomerNotice::select('customer_notices.*', 'multilang_contents.name as product_name', 'customers.last_name', 'customers.first_name')
        ->join('customers', 'customers.id', '=', 'customer_notices.customer_id')
        ->join('multilang_contents', 'multilang_contents.module_id', '=', 'customer_notices.product_id')
        ->where([
            'multilang_contents.module' => 'PRODUCT',
            'multilang_contents.lang_id' => 1
        ])->get();
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
            'notice' => 'required'
            ]);
        
        $notice = new CustomerNotice;
        $notice->notice = $request->notice;
        $notice->product_id = $request->product_id;
        $notice->customer_id = Auth::id();
        $notice->is_approved = 0;
        $notice->save();

        return response()->json('', 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CustomerNotice  $customerNotice
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return CustomerNotice::select('customer_notices.*', 'multilang_contents.name as product_name', 'customers.last_name', 'customers.first_name')
        ->join('customers', 'customers.id', '=', 'customer_notices.customer_id')
        ->join('multilang_contents', 'multilang_contents.module_id', '=', 'customer_notices.product_id')
        ->where([
            'multilang_contents.module' => 'PRODUCT',
            'multilang_contents.lang_id' => 1,
            'customer_notices.id' => $id
        ])->first();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\CustomerNotice  $customerNotice
     * @return \Illuminate\Http\Response
     */
    public function edit(CustomerNotice $customerNotice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\CustomerNotice  $customerNotice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $customerNotice = CustomerNotice::findOrFail($id);
        $customerNotice->is_approved = $customerNotice->is_approved === 1 ? 0 : 1;
        $customerNotice->save();

        return response()->json('', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\CustomerNotice  $customerNotice
     * @return \Illuminate\Http\Response
     */
    public function destroy(CustomerNotice $customerNotice)
    {
        //
    }
}
