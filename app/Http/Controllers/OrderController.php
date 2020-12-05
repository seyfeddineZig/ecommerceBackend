<?php

namespace App\Http\Controllers;

use App\Order;
use App\OrderDetail;
use App\StockMovement;
use App\Customer;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Order::select('orders.*', 'customers.last_name' , 'customers.first_name')
        ->join('customers', 'customers.id', '=', 'orders.customer_id')
        ->get();
    }

    public function getOrders(){

        return Order::where([
            'customer_id' => Auth::id()
        ])
        ->where('state_id', '!=', 6)
        ->get();
    }

    public function getCart(){

        $cart = Order::where(['customer_id' => Auth::id(), 'state_id' => 6])->first();

        if($cart){

            $cart->detail = OrderDetail::select('order_details.*', 
            'multilang_contents.name',
            'multilang_contents.lang_id',
            'product.name as product_name',
            'products.type',
            'products.default_image')
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
                ['order_details.order_id' => $cart->id,
                'product.module' => 'PRODUCT'
                ]
            )
            ->orderBy('order_details.created_at', 'asc')
            ->get();

            for($i=0; $i<count($cart->detail); $i++){
                if($cart->detail[$i]->type === 'KIT'){
                    $cart->detail[$i]->kitProducts = DB::table('kit_details')
                    ->select('kit_details.qty', 'multilang_contents.name')
                    ->join('multilang_contents', 'multilang_contents.module_id', '=', 'kit_details.product_id')
                    ->where([
                        'kit_details.kit_id' => $cart->detail[$i]->product_id,
                        'multilang_contents.module' => 'PRODUCT',
                        'multilang_contents.lang_id' => $cart->detail[$i]->lang_id
                    ])
                    ->get();
                }
            }
        }

        return response()->json($cart, 200);
    }

    public function getOrder($id){

        $order = Order::where(['customer_id' => Auth::id(), 'id' => $id])->first();
        if($order){
            $order->detail = OrderDetail::select('order_details.*', 
            'multilang_contents.name',
            'multilang_contents.lang_id',
            'product.name as product_name',
            'products.default_image')
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
                ['order_details.order_id' => $order->id,
                'product.module' => 'PRODUCT',
                ]
            )
            ->orderBy('order_details.created_at', 'asc')
            ->get();
        }

        return response()->json($order, 200);
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'rows.*.qty' => 'required| numeric',
            'rows.*.id' => 'required',
            'rows.*.module' => 'required',
            'type' => 'required'
        ]);

        $order = Order::where([
            'customer_id' => Auth::id(),
            'state_id' => 6
        ])->first();

        if(!$order){
            $order = new Order;
            $order->state_id = 6;
            $order->customer_id = Auth::id();
            $order->sub_total = 0;
        }
        
            if($request->type === 'PRODUCT'){
                if(count($request->rows) > 0){
                    $stock = DB::table('stocks')->where('product_id' , $request->rows[0]['product_id'])->first();
                    if(!$stock){
                        return response()->json(['error' => 'Le produit n\'est pas disponible'], 500);
                    }
                    $qty_sum = 0;
                    $virtual_qty = $stock->virtual_qty;

                    foreach ($request->rows as $key => $row) {
                        $qty_sum+= !empty($row['package_qty']) ? $row['package_qty']*$row['qty'] : $row['qty']; 
                    }
                    if($qty_sum > $stock->virtual_qty){
                        return response()->json(['error' => 'La quantité du produit est insuffisante'], 500);
                    }
                }
                else{
                    return response()->json(['error' => 'Veuillez sélectionner un produit'], 500);
                }
            }
            elseif($request->type === 'KIT'){

                $kit_detail = DB::table('kit_details')
                ->where(['kit_id' => $request->kit_id])
                ->get();

                foreach($kit_detail as $detail){
                    $stock = DB::table('stocks')->where('product_id' , $detail->product_id)->first();
                    if(!$stock){
                        return response()->json(['error' => 'Le produit n\'est pas disponible'], 500);
                    }    
                    elseif( ( $detail->qty*$request->qty ) > $stock->virtual_qty){
                        return response()->json(['error' => 'La quantité est insuffisante'], 500);
                    }
                }
            }
            else{
                return response()->json(['error' => 'Veuillez sélectionner un produit'], 500);
            }


        $kit_detail = isset($kit_detail) ? $kit_detail : null;
        $qty_sum = isset($qty_sum) ? $qty_sum : null;
        $virtual_qty = isset($virtual_qty) ? $virtual_qty : null;

        DB::transaction(function () use ($request, $order, $qty_sum, $virtual_qty, $kit_detail) {

            $order->save();
            $total = 0;
            $data = [];

            if($request->type === 'PRODUCT'){

                DB::table('stocks')->where('product_id', '=', $request->rows[0]['product_id'] )->update([
                    'virtual_qty' => $virtual_qty - $qty_sum
                ]);

                foreach ($request->rows as $key => $row) {

                    $order_detail = OrderDetail::where([
                        'module_id' => $row['id'],
                        'module' => $row['module'],
                        'order_id' => $order->id
                    ])->first();

                    if(!$order_detail){

                        $total+= ( $row['module'] === 'PRODUCT_PACKAGE' ? $row['package_qty'] : 1 ) * $row['qty']*$row['price'];

                        array_push($data, [
                            'order_id' => $order->id,
                            'module' => $row['module'],
                            'module_id' => $row['id'],
                            'qty' => $row['qty'],
                            'package_qty' => $row['module'] === 'PRODUCT_PACKAGE' ? $row['package_qty'] : null,
                            'amount' => $row['price'],
                            'product_id' => $row['product_id']
                        ]);
                    }
                }
            }
            elseif($request->type === 'KIT'){

                $total = $request->price * $request->qty;
                $order_detail = OrderDetail::where([
                    'module_id' => $request->kit_id,
                    'module' => 'PRODUCT',
                    'order_id' => $order->id,
                ])->first();

                if(!$order_detail){

                    foreach ($kit_detail as $detail) {

                            $detailStock = DB::table('stocks')->where('product_id', '=', $detail->product_id )->first();
                            DB::table('stocks')->where('product_id', '=', $detail->product_id )->update([
                                'virtual_qty' => $detailStock->virtual_qty - ( $detail->qty * $request->qty )
                            ]);
                    }

                    array_push($data, [
                        'order_id' => $order->id,
                        'module' => 'PRODUCT',
                        'module_id' => $request->kit_id,
                        'qty' => $request->qty,
                        'package_qty' => null,
                        'amount' => $request->price,
                        'product_id' => $request->kit_id
                    ]);
                        
                    
                }
            }

            $order->sub_total+=$total;
            $order->save();
            DB::table('order_details')->insert($data);
        });
        
    }

    public function deleteFromCart($id)
    {
        DB::transaction(function () use ($id) {

            $detail = OrderDetail::findOrFail($id);
            $order = Order::findOrFail($detail->order_id);

            if($detail->module === 'PRODUCT'){

                $product = DB::table('products')->where(['products.id' => $detail->module_id])->first();
                if( $product->type === 'KIT' ) {
                    $kit_details = DB::table('kit_details')->where(['kit_id' => $product->id])->get();
                    foreach($kit_details as $kit_detail ){
                        $stock = DB::table('stocks')->where('product_id', '=', $kit_detail->product_id )->first();
                        $qty = $kit_detail->qty * $detail->qty;
                        DB::table('stocks')
                        ->where( 'product_id', '=', $kit_detail->product_id )
                        ->update(['virtual_qty' => $stock->virtual_qty + $qty]);
                    }
                }
                else{
                    $stock = DB::table('stocks')->where('product_id', '=', $detail->product_id )->first();
                    $qty = $detail->qty;
                    DB::table('stocks')
                    ->where( 'product_id', '=', $detail->product_id )
                    ->update(['virtual_qty' => $stock->virtual_qty + $qty]);
                }
            }
            elseif($detail->module === 'PRODUCT_PACKAGE'){
                $stock = DB::table('stocks')->where('product_id', '=', $detail->product_id )->first();
                $qty = $detail->qty * $detail->package_qty;
                DB::table('stocks')
                ->where( 'product_id', '=', $detail->product_id )
                ->update(['virtual_qty' => $stock->virtual_qty + $qty]);
            }

            Order::where(['state_id' => 6, 'customer_id' => Auth::id()])->update([
                'sub_total' => $order->sub_total - (($detail->module === 'PRODUCT_PACKAGE' ? $detail->package_qty : 1 ) * $detail->qty*$detail->amount)
            ]);

            OrderDetail::join('orders', 'orders.id', '=', 'order_details.order_id')
            ->where([
                'customer_id' => Auth::id(),
                'orders.state_id' => 6,
                'order_details.id' => $id
            ])
            ->delete();

            $order_details = OrderDetail::where(['order_id' => $order->id])->get();
            if(count($order_details) === 0){
                Order::where(['id' => $order->id])->delete();
            }

        });

        return response()->json('', 200);
    }

    public function cancelOrder($id){

        $order = Order::findOrFail($id);
        if($order->state_id !== 9 && $order->state_id !== 2 ){
            DB::transaction(function () use ($order) {

                $order->state_id = 9;
                $order->save();
                app('App\Http\Controllers\ModuleStateController')->store('CUSTOMER_ORDER', $order->id, 9, Auth::id());

                
                $detail = OrderDetail::where('order_id' , '=', $order->id)->get();

                foreach($detail as $row){

                        $product = DB::table('products')
                                ->where(['id' => $row->product_id])
                                ->first();
                    
                        if($row->module === 'PRODUCT'){

                            if($product->type === 'PRODUCT'){

                                $stock = DB::table('stocks')
                                        ->where(['product_id' => $row->product_id])
                                        ->first();

                                $qty = $stock->virtual_qty + $row->qty;
                                $this->updateQty($row->module_id, 'virtual' ,$qty);
                            }

                            elseif($product->type === 'KIT'){

                                $kitProducts = DB::table('kit_details')
                                                ->where(['kit_id' => $row->product_id])
                                                ->get();

                                foreach($kitProducts as $kitProduct){
                                    $stock = DB::table('stocks')
                                    ->select('stocks.*', 'products.type')
                                    ->join('products', 'products.id', '=', 'stocks.product_id')
                                    ->where(['product_id' => $kitProduct->product_id])
                                    ->first();

                                    $qty = $stock->virtual_qty + ( $row->qty * $kitProduct->qty);
                                    $this->updateQty($kitProduct->product_id, 'virtual', $qty);
                                }
                            }
                        }
                        elseif($row->module === 'PRODUCT_PACKAGE'){

                            $stock = DB::table('stocks')
                                    ->where(['product_id' => $row->product_id])
                                    ->first();

                            $qty = $stock->virtual_qty + ($row->qty * $row->package_qty);
                            $this->updateQty($row->module_id, 'virtual', $qty);
                        }
                    }
            });
        }
        return response()->json('canceled', 200);
    }

    public function updateQty($module_id, $type, $qty){
        DB::table('stocks')->where([
            'product_id' => $module_id
        ])
        ->update(
            ['stocks.' . $type . '_qty' => $qty]
        );
    }

    public function saveStockMvt($product_id, $real_qty, $mvt_qty, $code){

        $stock_mvt = new StockMovement;
        $stock_mvt->module_id = $product_id;
        $stock_mvt->module = 'PRODUCT';
        $stock_mvt->stock_qty = $real_qty;
        $stock_mvt->mvt_qty = $mvt_qty;
        $stock_mvt->mvt_type = 'CUSTOMER_ORDER';
        $stock_mvt->piece_code = $code;
        $stock_mvt->save();

    }

    public function buyCart(Request $request)
    {

        DB::transaction(function () use ($request) {

            $coding = DB::table('codings')->first();
            $code = 'BC-' . $coding->customer_order_coding . '/' . $coding->year;
            DB::table('codings')->where(['id' =>  $coding->id])->update(['customer_order_coding' => $coding->customer_order_coding + 1]);
            
            Order::where([
                'customer_id' => Auth::id(),
                'state_id' => 6
            ])
            ->update([
                'state_id' => 3,
                'order_date' => DB::raw('NOW()'),
                'shipping_fees' => $request->shipping_fee,
                'shipping_adress' => $request->shipping_adress,
                'code' => $code
            ]);

        });
        return response()->json('', 200);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\order  $order
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order = Order::findOrFail($id);  

        $order->customer = Customer::select('customers.*', 'customer_categories_lang.name as category', 'customer_activities_lang.name as activity')
        ->join('customer_categories', 'customer_categories.id', '=', 'customers.category_id')
        ->join('customer_activities', 'customer_activities.id', '=', 'customers.activity_id')
        ->join('multilang_contents as customer_categories_lang', 'customer_categories_lang.module_id', '=', 'customer_categories.id')
        ->join('multilang_contents as customer_activities_lang', 'customer_activities_lang.module_id', '=', 'customer_activities.id')
        ->where([
            'customers.id' => $order->customer_id,
            'customer_activities_lang.module' => 'CUSTOMER_ACTIVITY',
            'customer_categories_lang.module' => 'CUSTOMER_CATEGORY',
            'customer_categories_lang.lang_id' => 1
        ])
        ->whereRaw(
            'customer_activities_lang.lang_id = customer_categories_lang.lang_id'
        )
        ->first();

        $order->states = DB::table('module_states')
        ->select('module_states.*', 'states.color', 'multilang_contents.name', DB::raw('concat(users.last_name, " ", users.first_name) as user'))
        ->join('states', 'states.id', '=', 'module_states.state_id')
        ->join('multilang_contents', 'multilang_contents.module_id', '=', 'states.id')
        ->join('users', 'users.id', '=', 'module_states.created_by')
        ->where([
            'module_states.module_id' => $order->id,
            'module_states.module' => 'CUSTOMER_ORDER',
            'multilang_contents.module' => 'STATE',
            'multilang_contents.lang_id' => 1
        ])->get();

        $order->payments = DB::table('payments')
        ->where([
            'order_id' => $order->id
        ])->get();

        $order->detail = OrderDetail::select('order_details.*', 
        'multilang_contents.name',
        'multilang_contents.lang_id',
        'product.name as product_name')
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
            ['order_details.order_id' => $order->id,
            'product.module' => 'PRODUCT',
            'multilang_contents.lang_id' => 1
            ]
        )
        ->orderBy('order_details.created_at', 'asc')
        ->get();
        

        return response()->json($order, 200);
    }


    public function getShippingDetail($id) {

        return OrderDetail::select('order_details.*', 
        'multilang_contents.name',
        'multilang_contents.lang_id',
        'product.name as product_name')
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
            ['order_details.order_id' => $id,
            'product.module' => 'PRODUCT',
            'multilang_contents.lang_id' => 1,
            ]
        )
        ->whereRaw('order_details.delivred_qty < order_details.qty')
        ->orderBy('order_details.created_at', 'asc')
        ->get();
    }

    public function getReturnDetail($id) {

        return OrderDetail::select('order_details.*', 
        'multilang_contents.name',
        'multilang_contents.lang_id',
        'product.name as product_name')
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
            ['order_details.order_id' => $id,
            'product.module' => 'PRODUCT',
            'multilang_contents.lang_id' => 1,
            ]
        )
        ->whereRaw('order_details.returned_qty < order_details.delivred_qty')
        ->orderBy('order_details.created_at', 'asc')
        ->get();
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'state_id' => 'required',
        ]);

        if($request->state_id === 9){
            return $this->cancelOrder($id);
        }
        elseif($request->state_id === 7){

            $order = Order::where(['id' => $id])
            ->update(['state_id' => 7]);
            app('App\Http\Controllers\ModuleStateController')->store('CUSTOMER_ORDER', $id, 7, Auth::id());

            return response()->json('', 200);

        }
        elseif($request->state_id === 8){
            
            return $this->getShippingDetail($id);
        }
        elseif($request->state_id === -1){
            
            return $this->getReturnDetail($id);
        }
        elseif($request->state_id === 2 ){

            $order = Order::where(['id' => $id])
            ->update(['state_id' => 2]);
            app('App\Http\Controllers\ModuleStateController')->store('CUSTOMER_ORDER', $id, 2, Auth::id());

            return response()->json('', 200);

        }
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(order $order)
    {
        //
    }
}
