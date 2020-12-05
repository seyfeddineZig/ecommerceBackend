<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PdfReport;
use Illuminate\Support\Facades\DB;
class reportingController extends Controller
{
    
public function printOrderShippingPiece($id)
{
    //$fromDate = $request->input('from_date');
    //$toDate = $request->input('to_date');
    //$sortBy = $request->input('sort_by');


    $piece = DB::table('order_shppings')
    ->select('order_shppings.*', 
            DB::raw('concat(customers.last_name, " ", customers.first_name) as customer'),
            'orders.shipping_adress'
            )
    ->join('orders', 'orders.id', '=', 'order_shppings.order_id')
    ->join('customers', 'customers.id', '=', 'orders.customer_id')
    ->where(['order_shppings.id' => $id])
    ->first();

    $queryBuilder = DB::table('order_shpping_details')
    ->select(['multilang_contents.name as name',
        'code',
        'order_shpping_details.qty as qty',
        'order_details.amount as amount', 
        DB::raw('POW(order_details.amount, order_shpping_details.qty) as total')])
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
        ['order_shpping_details.shipping_id' => $id,
        'product.module' => 'PRODUCT',
        'multilang_contents.lang_id' => 1
        ]
    )
    ->orderBy('order_details.created_at', 'asc');


    $title = 'Bon de livraison N° ' . $piece->shipping_code; // Report title

    $meta = [ // For displaying filters description on header

        'Client' => $piece->customer,
        'a' => 'loo',
        'b' => 'ool',
        'adresse de livraison' => $piece->shipping_adress,
  
    ];


    $columns = [ // Set Column to be displayed
        'code',
        'Désignation' => 'name', // if no column_name specified, this will automatically seach for snake_case of column name (will be registered_at) column from query result
        'Qté' => 'qty',
        'PU' => 'amount',
        'Total' => 'total'
        // 'Status' => function($result) { // You can do if statement or any action do you want inside this closure
        //     return ($result->balance > 100000) ? 'Rich Man' : 'Normal Guy';
        // }
    ];

    // Generate Report with flexibility to manipulate column class even manipulate column value (using Carbon, etc).
    return PdfReport::of($title, $meta, $queryBuilder, $columns)
                    // ->editColumn('Registered At', [ // Change column class or manipulate its data for displaying to report
                    //     'displayAs' => function($result) {
                    //         return $result->registered_at->format('d M Y');
                    //     },
                    //     'class' => 'left'
                    // ])
                    // ->editColumns(['Total Balance', 'Status'], [ // Mass edit column
                    //     'class' => 'right bold'
                    // ])
                    // ->showTotal([ // Used to sum all value on specified column on the last table (except using groupBy method). 'point' is a type for displaying total with a thousand separator
                    //     'Total Balance' => 'point' // if you want to show dollar sign ($) then use 'Total Balance' => '$'
                    // ])
                    //->limit(20) // Limit record to be showed
                    ->editColumn('Désignation', [
                        'class' => 'padding'
                    ])
                    ->setCss([
                        '.padding' => 'padding: 15px;',
                        '.bold' => 'font-weight: bold'
                    ])
                    ->showNumColumn(false)
                    ->stream(); // other available method: download('filename') to download pdf / make() that will producing DomPDF / SnappyPdf instance so you could do any other DomPDF / snappyPdf method such as stream() or download()
}
}
