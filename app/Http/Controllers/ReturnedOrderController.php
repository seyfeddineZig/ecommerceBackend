<?php

namespace App\Http\Controllers;

use App\ReturnedOrder;
use App\ReturnedOrderDetail;
use App\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Auth;

class ReturnedOrderController extends Controller
{
       /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return ReturnedOrder::select('returned_orders.*', 'orders.code as order_code', 'customers.last_name' , 'customers.first_name')
        ->join('orders', 'orders.id', '=', 'returned_orders.order_id')
        ->join('customers', 'customers.id', '=', 'orders.customer_id')
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

    public function getOrderDetail($id){

        return  DB::table('order_details')
                ->select('order_details.*', 'stocks.real_qty', 'stocks.virtual_qty', 'products.type')
                ->leftJoin('stocks' , 'stocks.product_id', '=', 'order_details.product_id')
                ->join('products' , 'products.id', '=', 'order_details.product_id')
                ->where(['order_details.id' => $id])
                ->first();
                
    }

    public function store(Request $request)
    {
            $request->validate([
                'rows' => 'required',
                'rows.*.id' => 'required',
                'rows.*.qty_to_return' => 'required',
                ]);

            foreach($request->rows as $key => $row){
                
                if($key === 0){
                    $order_id = $row['order_id'];
                }

                $order_detail = $this->getOrderDetail($row['id']);

                if($order_detail){

                    if($row['qty_to_return'] > ($order_detail->qty - $order_detail->returned_qty)){
                        return response()->json(['error' => 'La quantité à retourner du produit ' . $row['name'] . ' est supérieur de la quantité restante.'], 500);
                    }
                }
                else{
                    return response()->json(['error' => $row['name'] . ' n\'existe pas dans cette commande'], 500);
                }

            }

            $returned_order = new ReturnedOrder;
            $returned_order->order_id = $order_id;
            $returned_order->note = $request->note;
            //$returned_order->created_by = Auth::id();

            

            DB::transaction(function () use ($request, $returned_order) {

                $coding = DB::table('codings')->first();
                $code = 'BR-' . $coding->return_coding . '/' . $coding->year;
                DB::table('codings')->where(['id' =>  $coding->id])->update(['return_coding' => $coding->return_coding + 1]);

                $returned_order->return_code = $code;
                $returned_order->save();

                $data = [];

                foreach ($request->rows as $key => $row) {

                    $order_detail = $this->getOrderDetail($row['id']);

                    array_push($data, [
                        'return_id' => $returned_order->id,
                        'order_detail_id' => $row['id'],
                        'qty' => $row['qty_to_return'],
                    ]);

                    DB::table('order_details')
                    ->where([
                        'id' => $row['id']
                    ])
                    ->update([
                        'returned_qty' => $order_detail->returned_qty + $row['qty_to_return']
                    ]);
                    
                    if($order_detail->type === 'KIT'){

                        $kit_details = DB::table('kit_details')
                                        ->select('kit_details.*', 'stocks.real_qty', 'stocks.virtual_qty')
                                        ->join('stocks', 'stocks.product_id', '=', 'kit_details.product_id')
                                        ->where([
                                            'kit_details.kit_id' => $row['product_id']
                                        ])
                                        ->get();

                        foreach($kit_details as $kit_detail){
                            
                            $qty_to_return = $row['qty_to_return'] * $kit_detail->qty;

                            DB::table('stocks')
                            ->where([
                                'product_id' => $kit_detail->product_id
                            ])
                            ->update([
                                'real_qty' =>  $kit_detail->real_qty + $qty_to_return,
                                'virtual_qty' =>  $kit_detail->virtual_qty + $qty_to_return
                            ]);

                            $this->saveStockMvt($kit_detail->product_id, $kit_detail->real_qty, $qty_to_return, $code);

                        }  
                    }
                    else{

                        $qty_to_return = $order_detail->module === 'PRODUCT_PACKAGE' ? $row['qty_to_return'] * $order_detail->package_qty : $row['qty_to_return'];
                        
                        DB::table('stocks')
                        ->where([
                            'product_id' => $order_detail->product_id
                        ])
                        ->update([
                            'real_qty' =>  $order_detail->real_qty + $qty_to_return,
                            'virtual_qty' =>  $order_detail->virtual_qty + $qty_to_return
                        ]);

                        $this->saveStockMvt($order_detail->product_id, $order_detail->real_qty, $qty_to_return, $code);

                    }

                }

                DB::table('returned_order_details')->insert($data);

            });

            return response()->json([], 200);
    }

    public function saveStockMvt($product_id, $real_qty, $mvt_qty, $code){

        $stock_mvt = new StockMovement;
        $stock_mvt->module_id = $product_id;
        $stock_mvt->module = 'PRODUCT';
        $stock_mvt->stock_qty = $real_qty;
        $stock_mvt->mvt_qty = $mvt_qty;
        $stock_mvt->mvt_type = 'RETURN';
        $stock_mvt->piece_code = $code;
        $stock_mvt->save();

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\ReturnedOrder  $ReturnedOrder
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $returned_order = ReturnedOrder::select('returned_orders.*',
        'orders.code as order_code',
        'orders.shipping_adress',
        'orders.sub_total',
        'orders.shipping_fees',
        DB::raw('concat(customers.last_name, " ", customers.first_name) as customer'))
        ->join('orders', 'orders.id', '=', 'returned_orders.order_id')
        ->join('customers', 'customers.id', '=', 'orders.customer_id')
        ->where(['returned_orders.id' => $id])->first();

        if($returned_order){
            $returned_order->detail = ReturnedOrderDetail::select('returned_order_details.*', 
            'order_details.amount',
            'order_details.module',
            'order_details.module_id',
            'order_details.product_id',
            'order_details.package_qty',
            'multilang_contents.name',
            'multilang_contents.lang_id',
            'product.name as product_name')
            ->join('order_details', 'order_details.id', '=', 'returned_order_details.order_detail_id')
            ->join('multilang_contents', [
                'multilang_contents.module_id' => 'order_details.module_id',
                'multilang_contents.module' => 'order_details.module'
            ])
            ->join('multilang_contents as product', [
                'product.module_id' => 'order_details.product_id',
                'product.lang_id' => 'multilang_contents.lang_id'
            ])
            ->join('products', [
                'products.id' => 'order_details.product_id'
            ])
            ->where(
                ['returned_order_details.return_id' => $returned_order->id,
                'product.module' => 'PRODUCT',
                'multilang_contents.lang_id' => 1
                ]
            )
            ->orderBy('order_details.created_at', 'asc')
            ->get();
        }

        return response()->json($returned_order, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ReturnedOrder  $ReturnedOrder
     * @return \Illuminate\Http\Response
     */
    public function edit(ReturnedOrder $ReturnedOrder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\ReturnedOrder  $ReturnedOrder
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ReturnedOrder $ReturnedOrder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ReturnedOrder  $ReturnedOrder
     * @return \Illuminate\Http\Response
     */
    public function destroy(ReturnedOrder $ReturnedOrder)
    {
        //
    }
}
