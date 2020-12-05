<?php

namespace App\Http\Controllers;

use App\PaymentDeadline;
use Illuminate\Http\Request;

class PaymentDeadlineController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return PaymentDeadline::all();
    }

    public function getPaymentDeadline()
    {
        return PaymentDeadline::select('id', 'days as label', 'days')->get();
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
            'field' => 'required|numeric'
        ]);

        $obj = new PaymentDeadline;
        $obj->days = $request->field;
        $obj->save();

        return response()->json($obj, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\PaymentDeadline  $paymentDeadline
     * @return \Illuminate\Http\Response
     */
    public function show(PaymentDeadline $paymentDeadline)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\PaymentDeadline  $paymentDeadline
     * @return \Illuminate\Http\Response
     */
    public function edit(PaymentDeadline $paymentDeadline)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\PaymentDeadline  $paymentDeadline
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'field' => 'required|numeric'
        ]);

        $obj = PaymentDeadline::findOrFail($id);
        $obj->days = $request->field;
        $obj->save();

        return response()->json($obj, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\PaymentDeadline  $paymentDeadline
     * @return \Illuminate\Http\Response
     */
    public function destroy(PaymentDeadline $paymentDeadline)
    {
        //
    }
}
