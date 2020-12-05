<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/authUser', 'UserController@getAuthUser');


Route::middleware('auth:api')->group(function () {

    Route::post('/logout', 'UserController@logout');
    Route::resource('/lang', 'LangController');
    Route::resource('/productCategory', 'ProductCategoryController')->middleware('CheckRole:PRODUCT_CATEGORY');
    Route::resource('/user', 'UserController')->middleware('CheckRole:USER');
    Route::resource('/stock', 'StockController')->middleware('CheckRole:STOCK');
    Route::resource('/stockMovement', 'StockMovementController')->middleware('CheckRole:STOCK_MOVEMENT');
    Route::resource('/userGroup', 'UserGroupController')->middleware('CheckRole:USER_GROUP');
    Route::resource('/brand', 'BrandController')->middleware('CheckRole:BRAND');
    Route::resource('/pub', 'PubController')->middleware('CheckRole:PUB');
    Route::resource('/attribute', 'AttributeController')->middleware('CheckRole:ATTRIBUTE');
    Route::resource('/customer', 'CustomerController')->middleware('CheckRole:CUSTOMER');
    Route::resource('/customerCategory', 'CustomerCategoryController')->middleware('CheckRole:CUSTOMER_CATEGORY');
    Route::resource('/customerActivity', 'CustomerActivityController')->middleware('CheckRole:CUSTOMER_ACTIVITY');
    Route::resource('/pricing', 'PricingController')->middleware('CheckRole:CUSTOMER_CATEGORY');
    Route::resource('/product', 'ProductController')->middleware('CheckRole:PRODUCT');
    //Route::resource('/kit', 'KitController')->middleware('CheckRole:KIT');
    Route::resource('/productPackage', 'ProductPackageController');
    Route::post('/duplicateProduct/{id}', 'ProductController@duplicateProduct')->middleware('CheckRole:POST_PRODUCT');
    
    Route::resource('/state', 'StateController');
    Route::resource('/inventory', 'InventoryController')->middleware('CheckRole:INVENTORY');
    Route::resource('/input', 'InputController')->middleware('CheckRole:INPUT');
    Route::resource('/output', 'OutputController')->middleware('CheckRole:OUTPUT');
    Route::resource('/shippingFee', 'ShippingFeesController')->middleware('CheckRole:SHIPPING_FEE');
    Route::resource('/order', 'OrderController')->middleware('CheckRole:ORDER');
    Route::put('/validateInventory/{id}', 'InventoryController@validateInventory')->middleware('CheckRole:VALIDATE_INVENTORY');
    Route::put('/validateInput/{id}', 'InputController@validateInput')->middleware('CheckRole:VALIDATE_INPUT');
    Route::put('/validateOutput/{id}', 'OutputController@validateOutput')->middleware('CheckRole:VALIDATE_OUTPUT');
    Route::post('/loadProductFiles', 'ProductController@loadProductFiles')->middleware('CheckRole:IMPORT_PRODUCT_FILES');
    Route::post('/importProductFiles', 'ProductController@importProductFiles')->middleware('CheckRole:IMPORT_PRODUCT_FILES');
    Route::get('/getCustomerSearches', 'CustomerSearchController@index');
    Route::post('/getSearchedItemDetails', 'CustomerSearchController@show');
    Route::get('/customerNotice', 'CustomerNoticeController@index');
    Route::get('/customerNotice/{id}', 'CustomerNoticeController@show');
    Route::post('/setCustomerNoticeState/{id}', 'CustomerNoticeController@update');
    Route::resource('/orderShipping', 'OrderShppingController')->middleware('CheckRole:SHIPPING');
    Route::resource('/returnedOrder', 'ReturnedOrderController')->middleware('CheckRole:RETURN');
    Route::resource('/payment', 'PaymentController');
    Route::resource('/chat', 'ChatController');
    Route::resource('/entreprise', 'EntrepriseController');
    Route::resource('/paymentMode', 'PaymentModeController');
    Route::resource('/paymentDeadline', 'PaymentDeadlineController');
    Route::resource('/maxDebt', 'MaxDebtController');

    Route::post('/saveContractItems', 'PageController@saveContractItems');
    Route::get('printOrderShippingPiece/{id}', 'ReportingController@printOrderShippingPiece');
    Route::post('getDashboardData', 'DashboardController@getDashboardData');

    Route::post('/updateContract', 'CustomerController@updateContract');
});

Route::get('/getContractItems', 'PageController@getContractItems');
Route::get('/getPaymentMode', 'PaymentModeController@getPaymentMode');
Route::get('/getPaymentDeadline', 'PaymentDeadlineController@getPaymentDeadline');
Route::get('/getMaxDebt', 'MaxDebtController@getMaxDebt');


Route::get('/broadcast', function(){
    broadcast(new App\Events\Chat());
});




Route::get('/langs', 'LangController@index');
Route::post('/login', 'UserController@login');

Route::get('/customer_categories', 'CustomerCategoryController@index2');
Route::get('/customer_activities', 'CustomerActivityController@index2');
Route::get('/pubs', 'PubController@pubs');

Route::middleware('auth:customer')->get('/auth', 'CustomerController@getAuth');

Route::middleware('auth:customer')->group(function () {

    Route::get('/products', 'ProductController@index2');
    Route::post('/saveContract', 'CustomerController@saveContract');
    Route::post('/disconnect', 'CustomerController@logout');
    Route::get('/shippingFees', 'ShippingFeesController@index2');
    Route::get('/products/{id}', 'ProductController@show2');
    Route::get('/productsByCategory/{id}', 'ProductController@getProductsByCategory');
    Route::get('/productsByBrand/{id}', 'ProductController@getProductsByBrand');

    Route::post('/deleteFromCart/{id}', 'OrderController@deleteFromCart');
    Route::post('/addToCart', 'OrderController@addToCart');
    Route::get('/getCart', 'OrderController@getCart');
    Route::get('/orders', 'OrderController@getOrders');
    Route::get('/orders/{id}', 'OrderController@getOrder');
    Route::post('/buyCart', 'OrderController@buyCart');
    Route::post('/cancelOrder/{id}', 'OrderController@cancelOrder');
    Route::post('/alertWhenProductArrives/{id}', 'StockNotificationController@alertWhenProductArrives');

    Route::get('/states', 'StateController@index2');

    Route::post('/markAllNotificationsAsSeen', 'CustomerNotificationController@markAllNotificationsAsSeen');
    Route::get('/notifications', 'CustomerNotificationController@index');
    Route::get('/getRecentSearches', 'CustomerSearchController@getRecentSearches');
    Route::post('/saveSearch', 'CustomerSearchController@store');

    Route::post('/rateProduct', 'ProductRatingController@store');
    Route::post('/saveNotice', 'CustomerNoticeController@store');

    Route::get('/getMessages', 'ChatController@getMessages');
    Route::post('/sendMessage', 'ChatController@sendMessage');

    Route::post('/changeProfilePic', 'CustomerController@changeProfilePic');
    Route::post('/profile', 'CustomerController@updateProfile');
    Route::post('/changePassword', 'CustomerController@changePassword');

});

Route::post('/register', 'CustomerController@register');
Route::post('/auth', 'CustomerController@auth');
Route::get('/productsForVisitor', 'ProductController@indexForVisitor');
Route::get('/productsForVisitor/{id}', 'ProductController@showForVisitor');
Route::get('/productsByCategoryForVisitor/{id}', 'ProductController@getProductsByCategoryForVisitor');
Route::get('/productsByBrandForVisitor/{id}', 'ProductController@getProductsByBrandForVisitor');
Route::post('/saveSearchForVisitor', 'CustomerSearchController@store');

Route::get('/product_categories', 'ProductCategoryController@index2');
Route::get('/brands', 'BrandController@index2');
Route::get('/categories/{id}', 'ProductCategoryController@show2');
Route::get('/brands/{id}', 'BrandController@show2');
Route::post('/search', 'ProductController@search');
