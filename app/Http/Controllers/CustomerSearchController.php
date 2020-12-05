<?php

namespace App\Http\Controllers;

use App\CustomerSearch;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\DB;


class CustomerSearchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return CustomerSearch::select('multilang_contents.name', 'customer_searches.module_id', 'customer_searches.module', DB::raw('count(*) as searches_count'))
        ->join('multilang_contents', [
            'multilang_contents.module' => 'customer_searches.module',
            'multilang_contents.module_id' => 'customer_searches.module_id'
        ])
        ->where('multilang_contents.lang_id' ,'=' , 1)
        ->groupBy('multilang_contents.name', 'customer_searches.module','customer_searches.module_id')
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
            'module' => 'required',
            'module_id' => 'required'
            ]);

        $search = new CustomerSearch;
        $search->customer_id = Auth::check() ? Auth::id() : null;
        $search->module_id = $request->module_id;
        $search->module = $request->module;
        $search->save();

        return response()->json('', 200);
    }

    public function getRecentSearches () {
        
        return CustomerSearch::select('multilang_contents.name', 'customer_searches.module_id', 'customer_searches.module')
        ->join('multilang_contents', [
            'multilang_contents.module_id' => 'customer_searches.module_id',
            'multilang_contents.module' => 'customer_searches.module'
        ])
        ->where(
            'customer_searches.is_deleted' , '=', null
        )
        ->where('customer_searches.customer_id' , '=', Auth::id())
        ->orderBy('customer_searches.created_at', 'desc')
        ->get();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CustomerSearch  $customerSearch
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        return CustomerSearch::select('customers.last_name', 'customers.first_name', 'customer_searches.created_at')
        ->leftJoin('customers', [
            'customers.id' => 'customer_searches.customer_id'
        ])
        ->where('customer_searches.module' , '=', $request->module)
        ->where('customer_searches.module_id' , '=', $request->module_id)
        ->orderBy('customer_searches.created_at', 'desc')
        ->get();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\CustomerSearch  $customerSearch
     * @return \Illuminate\Http\Response
     */
    public function edit(CustomerSearch $customerSearch)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\CustomerSearch  $customerSearch
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CustomerSearch $customerSearch)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\CustomerSearch  $customerSearch
     * @return \Illuminate\Http\Response
     */
    public function destroy(CustomerSearch $customerSearch)
    {
        //
    }
}
