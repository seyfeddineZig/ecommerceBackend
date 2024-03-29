<?php

namespace App\Http\Controllers;

use App\Lang;
use Illuminate\Http\Request;

class LangController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Lang::all();
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
     * @param  \App\Lang  $lang
     * @return \Illuminate\Http\Response
     */
    public function show(Lang $lang)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Lang  $lang
     * @return \Illuminate\Http\Response
     */
    public function edit(Lang $lang)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Lang  $lang
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Lang $lang)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Lang  $lang
     * @return \Illuminate\Http\Response
     */
    public function destroy(Lang $lang)
    {
        //
    }
}
