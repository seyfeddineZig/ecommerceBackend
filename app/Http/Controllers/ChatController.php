<?php

namespace App\Http\Controllers;

use App\Message;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Auth;

class ChatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
            'customer_id' => 'required',
            'message' => 'required'
        ]);

        $message = new Message;
        $message->user_id = Auth::id();
        $message->customer_id = $request->customer_id;
        $message->message = $request->message;
        $message->is_deleted_by_user = false;
        $message->is_deleted_by_customer = false;
        $message->sender = 'USER';
        
        $message->save();
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required'
        ]);

        $message = new Message;
        $message->customer_id = Auth::id();
        $message->message = $request->message;
        $message->is_deleted_by_user = false;
        $message->is_deleted_by_customer = false;
        $message->sender = 'CUSTOMER';
        
        $message->save();

        broadcast(new MessageSent($message));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Message::select('messages.*', 
            DB::raw('concat(customers.last_name, " ", customers.first_name) as customer_name'),
            DB::raw('concat(users.last_name, " ", users.first_name) as user_name'))
            ->join('customers', 'customers.id', '=', 'messages.customer_id')
            ->leftJoin('users', 'users.id', '=', 'messages.user_id')
            ->where([
                'messages.customer_id' => $id
            ])
            ->get();
    }

    public function getMessages()
    {
        return Message::select('messages.message', 
        'messages.sender',
        'messages.created_at',
        DB::raw('concat(customers.last_name, " ", customers.first_name) as customer_name'),
        DB::raw('concat(users.last_name, " ", users.first_name) as user_name'))
        ->join('customers', 'customers.id', '=', 'messages.customer_id')
        ->leftJoin('users', 'users.id', '=', 'messages.user_id')
        ->where([
            'messages.customer_id' => Auth::id()
        ])
        ->get();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
