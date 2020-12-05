<?php

namespace App\Http\Controllers;

use App\OrderShpping;
use App\OrderShppingDetail;
use App\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Auth;
class OrderShppingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return OrderShpping::select('order_shppings.*', 'orders.code as order_code', 'customers.last_name' , 'customers.first_name')
        ->join('orders', 'orders.id', '=', 'order_shppings.order_id')
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
                ->select('order_details.*', 'stocks.real_qty', 'products.type')
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
                'rows.*.qty_to_deliver' => 'required',
                ]);

            foreach($request->rows as $key => $row){
                
                if($key === 0){
                    $order_id = $row['order_id'];
                }

                $order_detail = $this->getOrderDetail($row['id']);

                if($order_detail){

                    if($row['qty_to_deliver'] > ($order_detail->qty - $order_detail->delivred_qty)){
                        return response()->json(['error' => 'La quantité à livrer du produit ' . $row['name'] . ' est supérieur de la quantité restante.'], 500);
                    }
                    else {
                        if($order_detail->type === 'KIT'){

                            $kit_details = DB::table('kit_details')
                                            ->select('kit_details.*', 'stocks.real_qty')
                                            ->join('stocks', 'stocks.product_id', '=', 'kit_details.product_id')
                                            ->where([
                                                'kit_id' => $order_detail->product_id
                                            ])
                                            ->get();

                            foreach($kit_details as $kit_detail) {
                                if($kit_detail->real_qty < ( $row['qty_to_deliver'] * $kit_detail->qty ) ){
                                    return response()->json(['error' => 'La quantité du produit ' . $row['name'] . ' est insuffisante.'], 500);
                                }
                            }
                        }
                        else {

                            $qty_to_deliver = $order_detail->module === 'PRODUCT_PACKAGE' ? $row['qty_to_deliver'] * $order_detail->package_qty : $row['qty_to_deliver'];
                            if($qty_to_deliver > $order_detail->real_qty){
                                return response()->json(['error' => 'La quantité du produit ' . $row['name'] . ' est insuffisante.'], 500);
                            }
                        }
                    }
                }
                else{
                    return response()->json(['error' => $row['name'] . ' n\'existe pas dans cette commande'], 500);
                }

            }

            $order_shipping = new OrderShpping;
            $order_shipping->order_id = $order_id;
            $order_shipping->note = $request->note;
            //$order_shipping->created_by = Auth::id();

            

            DB::transaction(function () use ($request, $order_shipping) {

                $coding = DB::table('codings')->first();
                $code = 'BL-' . $coding->shipping_coding . '/' . $coding->year;
                DB::table('codings')->where(['id' =>  $coding->id])->update(['shipping_coding' => $coding->shipping_coding + 1]);

                $order_shipping->shipping_code = $code;
                $order_shipping->save();

                $data = [];

                foreach ($request->rows as $key => $row) {

                    $order_detail = $this->getOrderDetail($row['id']);

                    array_push($data, [
                        'shipping_id' => $order_shipping->id,
                        'order_detail_id' => $row['id'],
                        'qty' => $row['qty_to_deliver'],
                    ]);

                    DB::table('order_details')
                    ->where([
                        'id' => $row['id']
                    ])
                    ->update([
                        'delivred_qty' => $order_detail->delivred_qty + $row['qty_to_deliver']
                    ]);
                    
                    if($order_detail->type === 'KIT'){

                        $kit_details = DB::table('kit_details')
                                        ->select('kit_details.*', 'stocks.real_qty')
                                        ->join('stocks', 'stocks.product_id', '=', 'kit_details.product_id')
                                        ->where([
                                            'kit_details.kit_id' => $row['product_id']
                                        ])
                                        ->get();

                        foreach($kit_details as $kit_detail){
                            
                            $qty_to_deliver = $row['qty_to_deliver'] * $kit_detail->qty;

                            DB::table('stocks')
                            ->where([
                                'product_id' => $kit_detail->product_id
                            ])
                            ->update([
                                'real_qty' =>  $kit_detail->real_qty - $qty_to_deliver
                            ]);

                            $this->saveStockMvt($kit_detail->product_id, $kit_detail->real_qty, $qty_to_deliver, $code);

                        }  
                    }
                    else{

                        $qty_to_deliver = $order_detail->module === 'PRODUCT_PACKAGE' ? $row['qty_to_deliver'] * $order_detail->package_qty : $row['qty_to_deliver'];
                        
                        DB::table('stocks')
                        ->where([
                            'product_id' => $order_detail->product_id
                        ])
                        ->update([
                            'real_qty' =>  $order_detail->real_qty - $qty_to_deliver
                        ]);

                        $this->saveStockMvt($order_detail->product_id, $order_detail->real_qty, $qty_to_deliver, $code);

                    }

                }

                DB::table('order_shpping_details')->insert($data);

                // $order_details_not_delivred = DB::table('order_details')
                //                                 ->whereRaw('qty > delivred_qty')
                //                                 ->where('order_id', $order_shipping->order_id)
                //                                 ->get();

                // $state_id = count($order_details_not_delivred) === 0 ? 2 : 8;
                DB::table('orders')
                ->where(['id' => $order_shipping->order_id])
                ->update([
                    'state_id' => 8
                ]);
                app('App\Http\Controllers\ModuleStateController')->store('CUSTOMER_ORDER', $order_shipping->order_id, 8, Auth::id());


            });

            return response()->json([], 200);
    }

    public function saveStockMvt($product_id, $real_qty, $mvt_qty, $code){

        $stock_mvt = new StockMovement;
        $stock_mvt->module_id = $product_id;
        $stock_mvt->module = 'PRODUCT';
        $stock_mvt->stock_qty = $real_qty;
        $stock_mvt->mvt_qty = $mvt_qty;
        $stock_mvt->mvt_type = 'SHIPPING';
        $stock_mvt->piece_code = $code;
        $stock_mvt->save();

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\OrderShpping  $OrderShpping
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order_shipping = OrderShpping::select('order_shppings.*',
        'orders.code as order_code',
        'orders.shipping_adress',
        'orders.sub_total',
        'orders.shipping_fees',
        DB::raw('concat(customers.last_name, " ", customers.first_name) as customer'))
        ->join('orders', 'orders.id', '=', 'order_shppings.order_id')
        ->join('customers', 'customers.id', '=', 'orders.customer_id')
        ->where(['order_shppings.id' => $id])->first();

        if($order_shipping){
            $order_shipping->detail = OrderShppingDetail::select('order_shpping_details.*', 
            'order_details.amount',
            'order_details.module',
            'order_details.module_id',
            'order_details.product_id',
            'order_details.package_qty',
            'multilang_contents.name',
            'multilang_contents.lang_id',
            'product.name as product_name')
            ->join('order_details', 'order_details.id', '=', 'order_shpping_details.order_detail_id')
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
                ['order_shpping_details.shipping_id' => $order_shipping->id,
                'product.module' => 'PRODUCT',
                'multilang_contents.lang_id' => 1
                ]
            )
            ->orderBy('order_details.created_at', 'asc')
            ->get();
        }

        return response()->json($order_shipping, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\OrderShpping  $OrderShpping
     * @return \Illuminate\Http\Response
     */
    public function edit(OrderShpping $OrderShpping)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\OrderShpping  $OrderShpping
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, OrderShpping $OrderShpping)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\OrderShpping  $OrderShpping
     * @return \Illuminate\Http\Response
     */
    public function destroy(OrderShpping $OrderShpping)
    {
        //
    }
}
