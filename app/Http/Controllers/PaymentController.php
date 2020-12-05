<?php

namespace App\Http\Controllers;

use App\Payment;
use App\Order;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
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
            'type' => 'required',
            'order_id' => 'required',
            'amount' => 'required|numeric',
        ]);

        $order = Order::findOrFail($request->order_id);

        $order_payment = $order->payment + $request->amount;
        $order_total = $order->sub_total + $order->shipping_fees;
        
        if($request->type === 'PAYMENT'){

            if($order_payment > $order_total ){
                return response()->json( ['err' => 'Le paiement est supeérieur du montant total de la commande'], 500);
            }
            else{
                $order->payment = $order_payment;
            }

        }
        elseif($request->type === 'REFUND'){

            if($request->amount > $order->payment ){
                return response()->json( ['err' => 'Le montant à rembourser est supérieur du montant payé'], 500);
            }
            else{
                $order->payment = $order->payment - $request->amount;
            }

        }

        $payment = new Payment;
        $payment->amount = $request->amount;
        $payment->type = $request->type;
        $payment->order_id = $request->order_id;
        $payment->note = $request->note;
        $payment->created_by = Auth::id();

        DB::transaction(function () use ($request, $order, $payment) {

            $order->save();
            $payment->save();

        });

        return response()->json($payment, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function show(Payment $payment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function edit(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Payment $payment)
    {
        //
    }
}
