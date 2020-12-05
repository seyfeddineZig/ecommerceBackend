<?php

namespace App\Http\Controllers;

use App\InputOutput;
use App\InputOutputDetail;
use App\Stock;
use App\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Auth;

class InputController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return InputOutput::select('input_outputs.*',
        DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
        DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
           ->join('users as created_by', 'created_by.id', '=', 'input_outputs.created_by')
           ->leftJoin('users as updated_by', 'updated_by.id', '=', 'input_outputs.updated_by')
           ->where('input_outputs.type', '=', 'INPUT')
           ->orderBy('input_outputs.created_at', 'desc')
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
            'rows.*.qty' => 'required'
            ]);

            
            $input_output = new InputOutput;

            $input_output->created_by = Auth::id();
            $input_output->updated_by = Auth::id();

            $input_output->state_id = 1;
            $input_output->note = $request->note;
            $input_output->type = 'INPUT';
            $input_output->created_at = $request->date . ' ' . $request->time;


            DB::transaction(function () use ($request, $input_output) {

                $input_output->save();

                app('App\Http\Controllers\ModuleStateController')->store('INPUT', $input_output->id, 1, Auth::id());

                $data = [];
                foreach (json_decode($request->rows, true) as $key => $row) {

                    array_push($data, [
                        'product_id' => $row['product_id'],
                        'input_output_id' => $input_output->id,
                        'qty' => $row['qty'],
                        'note' => $row['note'],
                    ]);
                }

                DB::table('input_output_details')->insert($data);

            });
            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $input_output->created_by = $auth_user_full_name;
            $input_output->updated_by = $auth_user_full_name;
            return response()->json($input_output, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\InputOutput  $inputOutput
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $input = InputOutput::select('input_outputs.*',
        DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
        DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
           ->join('users as created_by', 'created_by.id', '=', 'input_outputs.created_by')
           ->leftJoin('users as updated_by', 'updated_by.id', '=', 'input_outputs.updated_by')
           ->where('input_outputs.id', '=', $id)
           ->first();

        $input->detail = InputOutputDetail::select('input_output_details.*',
           'products.code',
           'multilang_contents.name',
           'langs.full_name as lang',
           'langs.id as lang_id' )
              ->join('products', 'products.id', '=', 'input_output_details.product_id')
              ->join('multilang_contents', 'products.id', '=', 'multilang_contents.module_id')
              ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
              ->join('input_outputs', 'input_outputs.id', '=', 'input_output_details.input_output_id')
              ->where([
                'multilang_contents.module' => 'PRODUCT',
                'langs.id'     => 1,
                'input_outputs.id' => $id
              ])
              ->orderBy('products.id', 'desc')
              ->get();

        return response()->json( $input, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\InputOutput  $inputOutput
     * @return \Illuminate\Http\Response
     */
    public function edit(InputOutput $inputOutput)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\InputOutput  $inputOutput
     * @return \Illuminate\Http\Response
     */
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

            
            $input = InputOutput::findOrFail($id);

            if($input->state_id !== 1 ){
                return response()->json(['message' => 'Vous ne pouvez pas modifier une entrée fermée'], 500);
            }

            $input->updated_by = Auth::id();
            $input->created_at = $request->date . ' ' . $request->time;

            DB::transaction(function () use ($request, $input) {

                $input->save();
                
                $data = [];
                foreach (json_decode($request->rows, true) as $key => $row) {

                    array_push($data, [
                        'product_id' => $row['product_id'],
                        'input_output_id' => $input->id,
                        'qty' => $row['qty'],
                        'note' => $row['note'],
                    ]);
                }

                InputOutputDetail::where(['input_output_id' => $input->id])->delete();
                DB::table('input_output_details')->insert($data);

            });
            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $input->updated_by = $auth_user_full_name;
            return response()->json($input, 200);
    }

    public function validateInput(Request $request, $id)
    {
        $input = InputOutput::findOrFail($id);

        if($input->state_id !== 1 ){
            return response()->json(['message' => 'Cette entrée est déja fermée'], 500);
        }

        $input->state_id = 2; // Validated
            
        $coding = DB::table('codings')->first();
        $code = 'E-' . $coding->input_coding . '/' . $coding->year;
        $input->code = $code;
        DB::transaction(function () use ($input, $coding) {

            DB::table('codings')->where(['id' =>  $coding->id])->update(['input_coding' => $coding->input_coding + 1]);

            $input->save();

            app('App\Http\Controllers\ModuleStateController')->store('INPUT', $input->id, 2, Auth::id());

            $input_details = InputOutputDetail::where('input_output_id' , '=', $input->id)->get();
            forEach($input_details as $detail){

                $stock = Stock::where('product_id', '=', $detail->product_id)->first();
                $stock_qty=0;
                
                if($stock){
                    $stock_qty = $stock->real_qty;
                    $stock->real_qty+= $detail->qty;
                    $stock->virtual_qty+= $detail->qty;
                }
                else{
                    $stock = new Stock;
                    $stock->real_qty = $detail->qty;
                    $stock->virtual_qty = $detail->qty;
                    $stock->product_id = $detail->product_id;
                }

                $stock_mvt = new StockMovement;
                $stock_mvt->module_id = $detail->product_id;
                $stock_mvt->module = 'PRODUCT';
                $stock_mvt->stock_qty = $stock_qty;
                $stock_mvt->mvt_qty = $detail->qty;
                $stock_mvt->mvt_type = 'INPUT';
                $stock_mvt->piece_code = $input->code;
                    
                $stock->save();
                $stock_mvt->save();

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
        });

        return response()->json($code,200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\InputOutput  $inputOutput
     * @return \Illuminate\Http\Response
     */
    public function destroy(InputOutput $inputOutput)
    {
        //
    }
}
