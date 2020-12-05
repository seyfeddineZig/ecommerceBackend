<?php

namespace App\Http\Controllers;

use App\Customer;
use App\CustomerContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\File; 
use Illuminate\Support\Facades\DB;
use Auth;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Customer::all();
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
        //
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => ['required'],
            'last_name' => ['required'],
            'phone' => 'required|unique:customers',
            'email' => 'required|email|unique:customers',
            'password' => ['required'],
            'category_id' => ['required'],
            'activity_id' => ['required']
        ]);

        $customer = new Customer;
        $customer->first_name = $request->first_name;
        $customer->last_name = $request->last_name;
        $customer->category_id = $request->category_id;
        $customer->activity_id = $request->activity_id;
        $customer->email = $request->email;
        $customer->phone = $request->phone;
        $customer->password = Hash::make($request->password);
        $customer->points = 0;
        $customer->state_id = 3;
        $customer->save();

        $customer->token = $customer->createToken('Customer Auth Token')->accessToken;
        return response()->json($customer, 200);

    }


    public function updateProfile(Request $request){

        
        $request->validate([
            'first_name' => ['required'],
            'last_name' => ['required'],
            'phone' => 'required|unique:customers,phone,' . Auth::id(),
            'email' => 'required|email|unique:customers,phone,' . Auth::id(),
            'category_id' => ['required'],
            'activity_id' => ['required']
        ]);

        $customer = Customer::findOrFail(Auth::id());
        $customer->first_name = $request->first_name;
        $customer->last_name = $request->last_name;
        $customer->category_id = $request->category_id;
        $customer->activity_id = $request->activity_id;
        $customer->email = $request->email;
        $customer->phone = $request->phone;
        $customer->save();

        return response()->json($customer, 200);
    }

    public function changeProfilePic(Request $request){

        $request->validate([
            'image' => ['mimes:jpeg,jpg,png', 'max:10000'],
            ]);

        $customer = Customer::findOrFail(Auth::id());
        
        if(!empty($customer->image)){
            if(file_exists(public_path('images/' . $customer->image))){
                File::delete(public_path('images/' . $customer->image));
            }
        }


        $imageName = time() . '.' . $request->image->getClientOriginalExtension();
        $customer->image = $imageName;
        $request->image->move(public_path('images'), $imageName);
        $customer->save();

        return response()->json($imageName, 200);
        
    }
    
    public function saveContract(Request $request){

        if( Auth::user()->state_id === 3 ){

            $contract = CustomerContract::where([
                'customer_id' => Auth::id()
            ])->first();

            if(!$contract){
                $contract = new CustomerContract;
                $contract->customer_id = Auth::id();
            }

            $contract->max_debt_id = $request->max_debt_id;
            $contract->payment_deadline_id = $request->payment_deadline_id;
            $contract->payment_mode_id = $request->payment_mode_id;
            $contract->shipping = $request->shipping ? 1 : 0;
            $contract->save();
            

        }

        return response()->json('success', 200);

    }

    public function updateContract(Request $request){


        $contract = CustomerContract::where([
            'customer_id' => $request->customer_id
        ])->first();

        if(!$contract){
            $contract = new CustomerContract;
            $contract->customer_id = $request->customer_id;
        }

        $contract->max_debt_id = $request->max_debt_id;
        $contract->payment_deadline_id = $request->payment_deadline_id;
        $contract->payment_mode_id = $request->payment_mode_id;
        $contract->shipping = $request->shipping ? 1 : 0;
        $contract->save();

        return response()->json('success', 200);

    }

    public function importContract(Request $request) {

        $request->validate([
            'file' => ['required', 'mimes:pdf'],
            ]);

        $customer = Customer::findOrFail(Auth::id());
        $fileName = 'contract_' . Auth::id() . '.' . $request->file->getClientOriginalExtension();
        $customer->contract = $fileName;
        $customer->save();
        $request->file->move(public_path(''), $fileName);

        return response()->json('', 200);
        
    }

    public function changePassword(Request $request){

        $request->validate([
            'password' => 'required',
            'old_password' => 'required'
        ]);

        $customer = Customer::findOrFail(Auth::id());

        if(!Hash::check($request->old_password, $customer->password)){

            return response()->json('Les informations fournies sont incorrectes', 500);
        }

        $customer->password = Hash::make($request->password);
        $customer->save();

        return response()->json('success', 200);

    }

    public function getAuth(){
        $customer = Auth::user();
        $customer->contract = DB::table('customer_contracts')->where(['customer_id' => $customer->id])->first();
        return response()->json($customer);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Customer::select('customers.*', 
        'customer_category.name as category',
        'customer_activity.name as activity',
        'customer_contracts.payment_mode_id',
        'customer_contracts.payment_deadline_id',
        'customer_contracts.max_debt_id',
        'customer_contracts.shipping')
        ->join('customer_categories', 'customer_categories.id', '=', 'customers.category_id')
        ->join('customer_activities', 'customer_activities.id', '=', 'customers.activity_id')
        ->leftJoin('customer_contracts', 'customer_contracts.customer_id', '=', 'customers.id')
        ->join('multilang_contents as customer_category', 'customer_category.module_id', '=', 'customers.category_id')
        ->join('multilang_contents as customer_activity', 'customer_activity.module_id', '=', 'customers.activity_id')
        ->where(['customers.id' => $id,
        'customer_activity.module' => 'CUSTOMER_ACTIVITY',
        'customer_category.module' => 'CUSTOMER_CATEGORY'])
        ->first();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function edit(Customer $customer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'state_id' => ['required']]);

        $customer = Customer::findOrFail($id);
        $customer->state_id = $request->state_id;
        $customer->save();

        if($request->state_id === 5){

            DB::table('customer_notifications')->insert([
                'customer_id' => $customer->id,
                'product_id' => null,
                'type' => 'ACCOUNT_CONFIRMED',
                'seen' => 0
            ]);
        }

        return response()->json('', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Customer $customer)
    {
        //
    }

    public function auth(Request $request){

        $request->validate([
            'email'     => ['required', 'email'],
            'password'  => ['required']
        ]);

        $customer = Customer::where('email' , $request->email)->first();
        
        if(!$customer || !Hash::check($request->password, $customer->password)){

            throw ValidationException::withMessages([
                'error' => 'Les informations fournies sont incorrectes'
            ]);
 
        }
        $customer->token = $customer->createToken('Customer Auth Token')->accessToken;
        return response()->json($customer, 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
    }
}
