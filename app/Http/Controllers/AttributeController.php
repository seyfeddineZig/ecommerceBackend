<?php

namespace App\Http\Controllers;

use App\Attribute;
use App\AttributeValue;
use App\Lang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; 
use Illuminate\Support\Facades\File; 
use Auth; 


class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $attributes = Attribute::select('attributes.*',
            'multilang_contents.name',
            'multilang_contents.name as label',
            'multilang_contents.description',
            'langs.full_name as lang',
            'langs.id as lang_id' ,
            DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
            DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
            ->join('multilang_contents', 'attributes.id', '=', 'multilang_contents.module_id')
            ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
            ->join('users as created_by', 'created_by.id', '=', 'attributes.created_by')
            ->leftJoin('users as updated_by', 'updated_by.id', '=', 'attributes.updated_by')
            ->where('multilang_contents.module', 'ATTRIBUTE')
            ->where('langs.id', 1)
            ->orderBy('attributes.id', 'desc')
            ->get();


        $attribute_values = AttributeValue::select('attribute_values.*',
           'multilang_contents.name',
           'multilang_contents.name as label',
           'langs.full_name as lang',
           'langs.id as lang_id' )
            ->join('multilang_contents', 'attribute_values.id', '=', 'multilang_contents.module_id')
            ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
            ->where('multilang_contents.module', 'ATTRIBUTE_VALUE')
            ->where('langs.id', 1)
            ->orderBy('attribute_values.id', 'desc')
            ->get();

           return response()->json(['attributes' => $attributes,
           'attribute_values' => $attribute_values], 200);

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
            'is_activated' => ['required'],
            'attribute_type' => ['required'],
            'rows.*.name' => ['required', 'unique:attributes'],
            ]);

            $attribute = new Attribute;

            $attribute->is_activated = $request->is_activated;
            $attribute->type = $request->attribute_type;
            $attribute->created_by = Auth::id();
            $attribute->updated_by = Auth::id();
            

            DB::transaction(function () use ($request, $attribute) {

                $attribute->save();

                $data = [];
                foreach (json_decode($request->rows, true) as $key => $row) {

                    array_push($data, [
                        'name' => $row['name'],
                        'description' => $row['description'],
                        'lang_id' => $row['lang_id'],
                        'module' => 'ATTRIBUTE',
                        'module_id' => $attribute->id
                    ]);
                }
                
                $attribute_values = [];
                $array = json_decode($request->attribute_values, true);

                if( count($array) > 0 ){

                    foreach ( $array as $row) {


                        $attribute_value = new AttributeValue;
                        $attribute_value->attribute_id = $attribute->id;
                        $attribute_value->save();

                        foreach($row as $key => $r){

                            array_push($data, [
                                'name' => $r,
                                'description' => '',
                                'lang_id' => $key,
                                'module' => 'ATTRIBUTE_VALUE',
                                'module_id' => $attribute_value->id
                            ]);

                        }
 
                    }
                }


                DB::table('multilang_contents')->insert($data);

            });
            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $attribute->created_by = $auth_user_full_name;
            $attribute->updated_by = $auth_user_full_name;

            $attribute_values = AttributeValue::select('attribute_values.*',
            'multilang_contents.name',
            'multilang_contents.name as label',
            'langs.full_name as lang',
            'langs.id as lang_id' )
               ->join('multilang_contents', 'attribute_values.id', '=', 'multilang_contents.module_id')
               ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
               ->where([
               ['multilang_contents.module', '=', 'ATTRIBUTE_VALUE'],
               ['attribute_values.attribute_id' , '=', $attribute->id]]
               )->get();


            return response()->json(['attribute' => $attribute, 'attribute_values' => $attribute_values], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Attribute  $attribute
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $attribute = Attribute::select('attributes.*', 
        DB::raw('CONCAT(created_by.first_name , " " , created_by.last_name) as created_by'),
        DB::raw('CONCAT(updated_by.first_name , " " , updated_by.last_name) as updated_by'))
        ->join('users as created_by', 'created_by.id', '=', 'attributes.created_by')
        ->leftJoin('users as updated_by', 'updated_by.id', '=', 'attributes.updated_by')
        ->where(['attributes.id' => $id])
        ->first();

        $attribute->fields = DB::table('multilang_contents')
        ->where([
            'module' => 'ATTRIBUTE',
            'module_id' => $id
        ])->get();
        

        $attribute->attributeValues = DB::table('multilang_contents')
        ->join('attribute_values', 'attribute_values.id', '=', 'multilang_contents.module_id')
        ->where([
            'module' => 'ATTRIBUTE_VALUE',
            'attribute_values.attribute_id' => $id
        ])->get();
        
        return response()->json($attribute, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Attribute  $attribute
     * @return \Illuminate\Http\Response
     */
    public function edit(Attribute $attribute)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Attribute  $attribute
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'is_activated' => ['required'],
            'rows.*.name' => ['required']]);
            $attribute = Attribute::findOrFail($id);
            $last_attribute_value = AttributeValue::where(['attribute_id' => $attribute->id])->orderBy('id', 'DESC')->first();
            $attribute->is_activated = $request->is_activated;
            $attribute->updated_by = Auth::id();

            DB::transaction(function () use ($request, $attribute, $id) {

                $attribute->save();
                $data = [];

                DB::table('multilang_contents')->where(['module_id' => $attribute->id, 'module' => 'ATTRIBUTE'])->delete();

                foreach (json_decode($request->rows, true) as $key => $row) {

                    array_push($data, [
                        'name' => $row['name'],
                        'description' => $row['description'],
                        'lang_id' => $row['lang_id'],
                        'module' => 'ATTRIBUTE',
                        'module_id' => $attribute->id
                    ]);
                }

                foreach (json_decode($request->deleted_attribute_values, true) as $key => $id) {
                    DB::table('attribute_values')->where(['attribute_id' => $id])->delete();
                    DB::table('multilang_contents')->where(['module_id' => $id, 'module' => 'ATTRIBUTE_VALUE'])->delete();
                }

                foreach (json_decode($request->updated_attribute_values, true) as $key => $row) {
                    DB::table('multilang_contents')->where(['module' => 'ATTRIBUTE_VALUE', 'module_id' => $row['id'], 'lang_id' => $row['lang_id']])->update(
                        ['name' => $row['name']]
                    );
                }

                foreach (json_decode($request->attribute_values, true) as $row) {

                    $attribute_value = new AttributeValue;
                    $attribute_value->attribute_id = $attribute->id;
                    $attribute_value->save();

                    foreach($row as $key => $r){

                        array_push($data, [
                            'name' => $r,
                            'description' => '',
                            'lang_id' => $key,
                            'module' => 'ATTRIBUTE_VALUE',
                            'module_id' => $attribute_value->id
                        ]);

                    }

                }

                DB::table('multilang_contents')->insert($data);

            });
            $auth_user_full_name = Auth::user()->last_name . ' ' . Auth::user()->first_name;
            $attribute->updated_by = $auth_user_full_name;

             $attribute_values = AttributeValue::select('attribute_values.*',
             'multilang_contents.name',
             'multilang_contents.name as label',
             'langs.full_name as lang',
             'langs.id as lang_id' )
                ->join('multilang_contents', 'attribute_values.id', '=', 'multilang_contents.module_id')
                ->join('langs', 'multilang_contents.lang_id', '=', 'langs.id')
                ->where([
                ['multilang_contents.module', '=', 'ATTRIBUTE_VALUE'],
                ['attribute_values.attribute_id' , '=', $attribute->id],
                ['attribute_values.id' , '>', $last_attribute_value ? $last_attribute_value->id : 0 ] ]
                )->get();

            return response()->json(['attribute' => $attribute, 'attribute_values' => $attribute_values], 200);
            
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Attribute  $attribute
     * @return \Illuminate\Http\Response
     */
    public function destroy(Attribute $attribute)
    {
        //
    }
}
