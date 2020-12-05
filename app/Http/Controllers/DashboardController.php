<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getDashboardData(Request $request){

        $request->validate([
            'from' => 'required',
            'to' => 'required'
            ]);

        $from = date('Y-m-d h:i:s', strtotime($request->from));
        $to = date('Y-m-d h:i:s', strtotime($request->to));
        
        $customers = DB::table('customers')
        ->select(DB::raw('count(*) as count'), 'state_id as state')
        ->whereBetween('created_at', [$from, $to])
        ->groupBy('state_id')
        ->get();

        return response()->json([
            'customers' => $customers
        ],200);


    }
}
