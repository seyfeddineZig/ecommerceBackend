<?php

namespace App\Http\Controllers;

use App\Pub;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; 
use Illuminate\Support\Facades\File; 
use Auth; 

class PubController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Pub::all();
    }

    public function pubs()
    {
        return Pub::select('link', 'type', 'position', 'image')->get();
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
            'position' => ['required'],
            'type' => ['required'],
            'link' => ['required_if:type,==,VIDEO'],
            'image' => ['required_if:type,==,IMAGE', 'mimes:jpeg,jpg,png', 'max:10000']
            ]);

            $pub = new Pub;

            $pub->position = $request->position;
            $pub->type = $request->type;
            $pub->link = $request->link;
            $pub->created_by = Auth::id();
            $pub->updated_by = Auth::id();

            if (!empty($request->image)) {
                $imageName = time() . '.' . $request->image->getClientOriginalExtension();
                $pub->image = $imageName;
                $request->image->move(public_path('images'), $imageName);
            }
            $pub->save();

            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $pub->created_by = $auth_user_full_name;
            $pub->updated_by = $auth_user_full_name;

            return response()->json($pub, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Pub  $pub
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $pub = Pub::findOrfail($id);
        return response()->json($pub, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Pub  $pub
     * @return \Illuminate\Http\Response
     */
    public function edit(Pub $pub)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Pub  $pub
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $request->validate([
            'position' => ['required'],
            'type' => ['required'],
            'link' => ['required_if:type,==,VIDEO'],
            'image' => ['mimes:jpeg,jpg,png', 'max:10000']
            ]);

            $pub = Pub::findOrFail($id);

            $pub->position = $request->position;
            $pub->type = $request->type;
            $pub->link = $request->link;
            $pub->updated_by = Auth::id();

            if (!empty($request->image)) {
                $imageName = time() . '.' . $request->image->getClientOriginalExtension();
                $pub->image = $imageName;
                $request->image->move(public_path('images'), $imageName);
            }
            $pub->save();
            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $pub->updated_by = $auth_user_full_name;
            return response()->json($pub, 200);
    
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Pub  $pub
     * @return \Illuminate\Http\Response
     */
    public function destroy(Pub $pub)
    {
        //
    }
}
