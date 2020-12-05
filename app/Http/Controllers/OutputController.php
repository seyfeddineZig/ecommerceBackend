<?php

namespace App\Http\Controllers;

use App\InputOutput;
use App\InputOutputDetail;
use App\Stock;
use App\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Auth;

class OutputController extends Controller
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
           ->where('input_outputs.type', '=', 'OUTPUT')
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
            $input_output->type = 'OUTPUT';
            $input_output->created_at = $request->date . ' ' . $request->time;


            DB::transaction(function () use ($request, $input_output) {

                $input_output->save();

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
    public function show(InputOutput $inputOutput)
    {
        $output = InputOutput::select('input_outputs.*',
        DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
        DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
           ->join('users as created_by', 'created_by.id', '=', 'input_outputs.created_by')
           ->leftJoin('users as updated_by', 'updated_by.id', '=', 'input_outputs.updated_by')
           ->where('input_outputs.id', '=', $id)
           ->first();

        $output->detail = InputOutputDetail::select('input_output_details.*',
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

        return response()->json( $output, 200);
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
                return response()->json(['message' => 'Vous ne pouvez pas modifier une sortie fermée'], 500);
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

    public function validateOutput(Request $request, $id)
    {
        $input = InputOutput::findOrFail($id);

        if($input->state_id !== 1 ){
            return response()->json(['message' => 'Cette sortie est déja fermée'], 500);
        }

        $input_details = InputOutputDetail::where('input_output_id' , '=', $input->id)->get();
        
        forEach($input_details as $detail){

            $stock = Stock::where('product_id', '=', $detail->product_id)->first();
            
            $product = DB::table('multilang_contents')->select('name')->where([
                'module' => 'PRODUCT',
                'module_id' => $detail->product_id
            ])->first();
            
            if($stock){
                if($stock->real_qty < $detail->qty){
                    return response()->json(['message' => 'la quantité du produit ' . $product->name . ' est insuffisante'], 500);
                }
                if($stock->virtual_qty < $detail->qty){
                    return response()->json(['message' => 'la quantité virtuelle du produit ' . $product->name . ' est insuffisante'], 500);
                }
            }
            else{
                return response()->json(['message' => 'la quantité du produit ' . $product->name . ' est insuffisante'], 500);
            }

        }


        $input->state_id = 2; // Validated
            
        $coding = DB::table('codings')->first();
        $code = 'S-' . $coding->output_coding . '/' . $coding->year;
        $input->code = $code;
        DB::transaction(function () use ($input, $coding, $input_details) {

            DB::table('codings')->where(['id' =>  $coding->id])->update(['output_coding' => $coding->output_coding + 1]);

            $input->save();

            forEach($input_details as $detail){

                $stock = Stock::where('product_id', '=', $detail->product_id)->first();
                $stock_qty=0;
                
                if($stock){

                $stock_qty = $stock->real_qty;
                $stock->real_qty-= $detail->qty;
                $stock->virtual_qty-= $detail->qty;

                $stock_mvt = new StockMovement;
                $stock_mvt->module_id = $detail->product_id;
                $stock_mvt->module = 'PRODUCT';
                $stock_mvt->stock_qty = $stock_qty;
                $stock_mvt->mvt_qty = $detail->qty;
                $stock_mvt->mvt_type = 'OUTPUT';
                $stock_mvt->piece_code = $input->code;

                $stock->save();
                $stock_mvt->save();

                }
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
