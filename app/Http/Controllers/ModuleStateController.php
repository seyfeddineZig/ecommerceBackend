<?php

namespace App\Http\Controllers;

use App\ModuleState;
use Illuminate\Http\Request;

class ModuleStateController extends Controller
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
    public function store($module, $module_id, $state_id, $created_by)
    {
        $module_state = new ModuleState;
        $module_state->module = $module;
        $module_state->module_id = $module_id;
        $module_state->state_id = $state_id;
        $module_state->created_by = $created_by;
        $module_state->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\ModuleState  $moduleState
     * @return \Illuminate\Http\Response
     */
    public function show(ModuleState $moduleState)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ModuleState  $moduleState
     * @return \Illuminate\Http\Response
     */
    public function edit(ModuleState $moduleState)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\ModuleState  $moduleState
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ModuleState $moduleState)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ModuleState  $moduleState
     * @return \Illuminate\Http\Response
     */
    public function destroy(ModuleState $moduleState)
    {
        //
    }
}
