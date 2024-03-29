<?php

namespace App\Http\Controllers;

use App\Entreprise;
use Illuminate\Http\Request;

class EntrepriseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $entreprise = Entreprise::findOrFail(1);

        if(!$entreprise){

            $entreprise = new Entreprise;
            $entreprise->id = 1;
            $entreprise->name = '';
            $entreprise->email = '';
            $entreprise->phone = '';
            $entreprise->adress = '';
            $entreprise->save();
        }

        return response()->json($entreprise, 200);
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
        $entreprise = Entreprise::findOrFail(1);

        if(!$entreprise){
            
            $entreprise = new Entreprise;
            $entreprise->id = 1;

        }

        $entreprise->name = $request->name;
        $entreprise->email = $request->email;
        $entreprise->phone = $request->phone;
        $entreprise->adress = $request->adress;
        
        $entreprise->save();

        return response()->json($entreprise, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Entreprise  $entreprise
     * @return \Illuminate\Http\Response
     */
    public function show(Entreprise $entreprise)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Entreprise  $entreprise
     * @return \Illuminate\Http\Response
     */
    public function edit(Entreprise $entreprise)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Entreprise  $entreprise
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Entreprise $entreprise)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Entreprise  $entreprise
     * @return \Illuminate\Http\Response
     */
    public function destroy(Entreprise $entreprise)
    {
        //
    }
}
