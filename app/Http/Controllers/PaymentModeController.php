<?php

namespace App\Http\Controllers;

use App\PaymentMode;
use Illuminate\Http\Request;

class PaymentModeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return PaymentMode::all();
    }

    public function getPaymentMode()
    {
        return PaymentMode::select('id', 'name as label', 'name')->get();
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
            'field' => 'required'
        ]);

        $obj = new PaymentMode;
        $obj->name = $request->field;
        $obj->save();

        return response()->json($obj, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\PaymentMode  $paymentMode
     * @return \Illuminate\Http\Response
     */
    public function show(PaymentMode $paymentMode)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\PaymentMode  $paymentMode
     * @return \Illuminate\Http\Response
     */
    public function edit(PaymentMode $paymentMode)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\PaymentMode  $paymentMode
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'field' => 'required'
        ]);

        $obj = PaymentMode::findOrFail($id);
        $obj->name = $request->field;
        $obj->save();

        return response()->json($obj, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\PaymentMode  $paymentMode
     * @return \Illuminate\Http\Response
     */
    public function destroy(PaymentMode $paymentMode)
    {
        //
    }
}
