<?php

namespace App\Http\Controllers;

use App\MaxDebt;
use Illuminate\Http\Request;

class MaxDebtController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return MaxDebt::all();
    }

    public function getMaxDebt()
    {
        return MaxDebt::select('id', 'amount as label', 'amount')->get();
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

        $obj = new MaxDebt;
        $obj->amount = $request->field;
        $obj->save();

        return response()->json($obj, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\MaxDebt  $maxDebt
     * @return \Illuminate\Http\Response
     */
    public function show(MaxDebt $maxDebt)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\MaxDebt  $maxDebt
     * @return \Illuminate\Http\Response
     */
    public function edit(MaxDebt $maxDebt)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\MaxDebt  $maxDebt
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'field' => 'required|numeric'
        ]);

        $obj = MaxDebt::findOrFail($id);
        $obj->amount = $request->field;
        $obj->save();

        return response()->json($obj, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\MaxDebt  $maxDebt
     * @return \Illuminate\Http\Response
     */
    public function destroy(MaxDebt $maxDebt)
    {
        //
    }
}
