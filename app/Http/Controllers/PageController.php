<?php

namespace App\Http\Controllers;

use App\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageController extends Controller
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

    public function getContractItems()
    {
        return Page::select('pages.id', 'name', 'description', 'lang_id')
        ->join('multilang_contents', 'multilang_contents.module_id', '=', 'pages.id')
        ->where([
            'module' => 'PAGE'
        ])
        ->whereIn('pages.id', [1, 2])
        ->orderBy('pages.id', 'ASC')
        ->get();

    }

    public function saveContractItems(Request $request){

        $request->validate([
            'pages.*.id' => 'required',
            'pages.*.description' => 'required',
            'pages.*.lang_id' => 'required',
        ]);

        foreach($request->pages as $page){
            DB::table('multilang_contents')->where([
                'module_id' => $page['id'],
                'lang_id' => $page['lang_id'],
                'module' => 'PAGE' 
            ])
            ->update([
                'description' => $page['description']
            ]);
        }

        return response()->json('success', 200);

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
     * @param  \App\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function show(Page $page)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function edit(Page $page)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Page $page)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function destroy(Page $page)
    {
        //
    }
}
