<?php

namespace App\Http\Controllers;

use App\state;
use Illuminate\Http\Request;

class StateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return state::select('states.*',
        'multilang_contents.name',
        'langs.full_name as lang',
        'langs.id as lang_id' )
           ->join('multilang_contents', 'states.id', '=', 'multilang_contents.module_id')
           ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
           ->where('multilang_contents.module', 'STATE')
           ->where('langs.id', 1)
           ->orderBy('states.id', 'desc')
           ->get();
    }

    public function index2()
    {
        return state::select('states.*',
        'multilang_contents.name',
        'langs.full_name as lang',
        'langs.id as lang_id' )
           ->join('multilang_contents', 'states.id', '=', 'multilang_contents.module_id')
           ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
           ->where('multilang_contents.module', 'STATE')
           ->orderBy('states.id', 'desc')
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\state  $state
     * @return \Illuminate\Http\Response
     */
    public function show(state $state)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\state  $state
     * @return \Illuminate\Http\Response
     */
    public function edit(state $state)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\state  $state
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, state $state)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\state  $state
     * @return \Illuminate\Http\Response
     */
    public function destroy(state $state)
    {
        //
    }
}
