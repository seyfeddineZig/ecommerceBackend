<?php

namespace App\Http\Controllers;

use App\Inventory;
use App\InventoryDetail;
use App\Stock;
use App\StockMovement;
use Illuminate\Support\Facades\DB;
use Auth;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Inventory::select('inventories.*',
        DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
        DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
           ->join('users as created_by', 'created_by.id', '=', 'inventories.created_by')
           ->leftJoin('users as updated_by', 'updated_by.id', '=', 'inventories.updated_by')
           ->orderBy('inventories.created_at', 'desc')
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
            'date' => 'required',
            'time' => 'required',
            'rows' => 'required',
            'rows.*.product_id' => 'required',
            'rows.*.product_count' => 'required',
            'rows.*.product_qty' => 'required',
            ]);

            
            $inventory = new Inventory;

            $inventory->created_by = Auth::id();
            $inventory->updated_by = Auth::id();

            $inventory->state_id = 1;
            $inventory->created_at = $request->date . ' ' . $request->time;


            DB::transaction(function () use ($request, $inventory) {

                $inventory->save();

                app('App\Http\Controllers\ModuleStateController')->store('INVENTORY', $inventory->id, 1, Auth::id());

                $data = [];
                foreach (json_decode($request->rows, true) as $key => $row) {

                    array_push($data, [
                        'product_id' => $row['product_id'],
                        'inventory_id' => $inventory->id,
                        'stock_qty' => $row['product_qty'],
                        'count' => $row['product_count'],
                    ]);
                }

                DB::table('inventory_details')->insert($data);

            });
            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $inventory->created_by = $auth_user_full_name;
            $inventory->updated_by = $auth_user_full_name;
            return response()->json($inventory, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Inventory  $inventory
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $inventory = Inventory::select('inventories.*',
        DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
        DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
           ->join('users as created_by', 'created_by.id', '=', 'inventories.created_by')
           ->leftJoin('users as updated_by', 'updated_by.id', '=', 'inventories.updated_by')
           ->where('inventories.id', $id)
           ->first();

        $inventory->detail = InventoryDetail::select('inventory_details.*',
           'products.code',
           'multilang_contents.name',
           'langs.full_name as lang',
           'langs.id as lang_id' )
              ->join('products', 'products.id', '=', 'inventory_details.product_id')
              ->join('multilang_contents', 'products.id', '=', 'multilang_contents.module_id')
              ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
              ->where('multilang_contents.module', 'PRODUCT')
              ->where('langs.id', 1)
              ->where('inventory_details.inventory_id', $id)
              ->orderBy('products.id', 'desc')
              ->get();

              return response()->json($inventory, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Inventory  $inventory
     * @return \Illuminate\Http\Response
     */
    public function edit(Inventory $inventory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Inventory  $inventory
     * @return \Illuminate\Http\Response
     */
    public function validateInventory(Request $request, $id)
    {
        $inventory = Inventory::findOrFail($id);

        if($inventory->state_id !== 1 ){
            return response()->json(['message' => 'Cet inventaire est fermé'], 500);
        }

        $inventories = Inventory::whereDate('created_at', '>=', $inventory->created_at)->get();
        
        if( count($inventories) > 0 ){
            return response()->json(['message' => 'Vous ne pouvez pas valider cet inventaire car il y\'a des inventaires validés avec une date supèrieur de cette inventaire'], 500);
        }
        $inventory->state_id = 2; // Validated
            
        $coding = DB::table('codings')->first();
        $code = 'I-' . $coding->inventory_coding . '/' . $coding->year;
        $inventory->code = $code;
        DB::transaction(function () use ($inventory, $coding) {

            DB::table('codings')->where(['id' =>  $coding->id])->update(['inventory_coding' => $coding->inventory_coding + 1]);

            $inventory->save();

            app('App\Http\Controllers\ModuleStateController')->store('INVENTORY', $inventory->id, 2, Auth::id());


            $inventory_details = InventoryDetail::where('inventory_id' , '=', $inventory->id)->get();
            forEach($inventory_details as $detail){

                $stock = Stock::where('product_id', '=', $detail->product_id)->first();
                
                if($stock){
                    $stock->virtual_qty = $stock->virtual_qty - $stock->real_qty + $detail->count;
                    $stock->real_qty = $detail->count;
                }
                else{
                    $stock = new Stock;
                    $stock->real_qty = $detail->count;
                    $stock->virtual_qty = $detail->count;
                    $stock->product_id = $detail->product_id;
                }

                $stock_mvt = new StockMovement;
                $stock_mvt->module_id = $detail->product_id;
                $stock_mvt->module = 'PRODUCT';
                $stock_mvt->stock_qty = $detail->stock_qty;
                $stock_mvt->mvt_qty = $detail->count;
                $stock_mvt->mvt_type = 'INVENTORY';
                $stock_mvt->piece_code = $inventory->code;
                    
                $stock->save();
                $stock_mvt->save();

                if($detail->count > 0){

                    $stock_notifications = DB::table('stock_notifications')->where([
                        'closed' => 0,
                        'product_id' => $detail->product_id
                    ])->get();
                    
                    $notifications = [];
                    
                    foreach($stock_notifications as $notification) {

                        array_push($notifications, [
                            'customer_id' => $notification->customer_id,
                            'product_id' => $notification->product_id,
                            'type' => 'STOCK_ARRIVAL',
                            'seen' => 0
                        ]);

                    }

                    if( count($notifications) > 0 ){
                        DB::table('customer_notifications')->insert($notifications);
                    }

                    DB::table('stock_notifications')->where([
                        'closed' => 0,
                        'product_id' => $detail->product_id
                    ])->update([
                        'closed' => 1
                    ]);
                }

            }
        });

        return response()->json($code,200);

    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required',
            'time' => 'required',
            'rows' => 'required',
            'rows.*.product_id' => 'required',
            'rows.*.product_count' => 'required',
            'rows.*.product_qty' => 'required',
            ]);

            
            $inventory = Inventory::findOrFail($id);

            if($inventory->state_id !== 1 ){
                return response()->json(['message' => 'Vous ne pouvez pas modifier un inventaire fermé'], 500);
            }

            $inventory->updated_by = Auth::id();
            $inventory->created_at = $request->date . ' ' . $request->time;

            DB::transaction(function () use ($request, $inventory) {

                $inventory->save();
                
                $data = [];
                foreach (json_decode($request->rows, true) as $key => $row) {

                    array_push($data, [
                        'product_id' => $row['product_id'],
                        'inventory_id' => $inventory->id,
                        'stock_qty' => $row['product_qty'],
                        'count' => $row['product_count'],
                    ]);
                }

                InventoryDetail::where(['inventory_id' => $inventory->id])->delete();
                DB::table('inventory_details')->insert($data);

            });
            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $inventory->updated_by = $auth_user_full_name;
            return response()->json($inventory, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Inventory  $inventory
     * @return \Illuminate\Http\Response
     */
    public function destroy(Inventory $inventory)
    {
        //
    }
}
