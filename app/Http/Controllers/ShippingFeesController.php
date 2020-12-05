<?php

namespace App\Http\Controllers;

use App\ShippingFees;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Auth;

class ShippingFeesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return ShippingFees::select('shipping_fees.*',
        'multilang_contents.name',
        'multilang_contents.name as label',
        'langs.full_name as lang',
        'langs.id as lang_id' ,
        DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
        DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
           ->join('multilang_contents', 'shipping_fees.id', '=', 'multilang_contents.module_id')
           ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
           ->join('users as created_by', 'created_by.id', '=', 'shipping_fees.created_by')
           ->leftJoin('users as updated_by', 'updated_by.id', '=', 'shipping_fees.updated_by')
           ->where('multilang_contents.module', 'SHIPPING_FEE')
           ->where('langs.id', 1)
           ->orderBy('shipping_fees.id', 'desc')
           ->get();
    }

    public function index2()
    {
        return ShippingFees::select('shipping_fees.*',
        'multilang_contents.name',
        'multilang_contents.name as label',
        'langs.full_name as lang',
        'langs.id as lang_id')
           ->join('multilang_contents', 'shipping_fees.id', '=', 'multilang_contents.module_id')
           ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
           ->where('multilang_contents.module', 'SHIPPING_FEE')
           ->orderBy('shipping_fees.id', 'desc')
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
        $request->validate([
            'is_activated' => ['required'],
            'rows.*.name' => ['required'],
            'code' => ['required', 'unique:shipping_fees'],
            'amount' => ['required']
            ]);

            $shipping_fee = new ShippingFees;

            $shipping_fee->is_activated = $request->is_activated;
            $shipping_fee->amount = $request->amount;
            $shipping_fee->code = $request->code;

            $shipping_fee->created_by = Auth::id();
            $shipping_fee->updated_by = Auth::id();


            DB::transaction(function () use ($request, $shipping_fee) {

                $shipping_fee->save();

                $data = [];
                foreach (json_decode($request->rows, true) as $key => $row) {

                    array_push($data, [
                        'name' => $row['name'],
                        'description' => '',
                        'module' => 'SHIPPING_FEE',
                        'lang_id' => $row['lang_id'],
                        'module_id' => $shipping_fee->id
                    ]);
                }

                DB::table('multilang_contents')->insert($data);

            });
            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $shipping_fee->created_by = $auth_user_full_name;
            $shipping_fee->updated_by = $auth_user_full_name;
            return response()->json($shipping_fee, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\ShippingFees  $shippingFees
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $shipping_fee = ShippingFees::select('shipping_fees.*', 
        DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
        DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
        ->join('users as created_by', 'created_by.id', '=', 'shipping_fees.created_by')
        ->leftJoin('users as updated_by', 'updated_by.id', '=', 'shipping_fees.updated_by')
        ->where(['shipping_fees.id' => $id])
        ->first();

        $shipping_fee->fields = DB::table('multilang_contents')
        ->where([
            'module' => 'SHIPPING_FEE',
            'module_id' => $id
        ])->get();
        

        return response()->json($shipping_fee, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ShippingFees  $shippingFees
     * @return \Illuminate\Http\Response
     */
    public function edit(ShippingFees $shippingFees)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\ShippingFees  $shippingFees
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $request->validate([
            'is_activated' => ['required'],
            'rows.*.name' => ['required'],
            'amount' => ['required'],
            'code' => ['required', 'unique:shipping_fees,code,' . $id],
            ]);

            $shipping_fee = ShippingFees::findOrFail($id);

            $shipping_fee->is_activated = $request->is_activated;
            $shipping_fee->amount = $request->amount;
            $shipping_fee->code = $request->code;
            $shipping_fee->updated_by = Auth::id();

            DB::transaction(function () use ($request, $shipping_fee, $id) {

                $shipping_fee->save();

                $data = [];
                foreach (json_decode($request->rows, true) as $key => $row) {

                    array_push($data, [
                        'name' => $row['name'],
                        'description' => '',
                        'lang_id' => $row['lang_id'],
                        'module' => 'SHIPPING_FEE',
                        'module_id' => $shipping_fee->id
                    ]);
                }

                DB::table('multilang_contents')->where(['module_id' => $id, 'module' => 'SHIPPING_FEE'])->delete();
                DB::table('multilang_contents')->insert($data);

            });
            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $shipping_fee->updated_by = $auth_user_full_name;
            return response()->json($shipping_fee, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ShippingFees  $shippingFees
     * @return \Illuminate\Http\Response
     */
    public function destroy(ShippingFees $shippingFees)
    {
        //
    }
}
