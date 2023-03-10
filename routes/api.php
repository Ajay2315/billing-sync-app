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

Route::group([
    'middleware' => 'api',
    'namespace' => 'App\Http\Controllers\Api',
], function($router){
    //-------------------Billing Controller Start--------------------------// 
    Route::get('/SyncPaymentToHO', 'BillingController@SyncPayment');
    Route::post('/rebuildPaymentsWater', 'BillingController@rebuildPaymentsWater');
    Route::post('/rebuildPaymentsOthers', 'BillingController@rebuildPaymentsOthers');
    Route::post('/rebuildReading', 'ReadingController@rebuildReading');
    Route::post('/validate', 'BillingController@validatePaymentHeader');
    Route::get('/sync', 'ReadingController@uploadEfficiencyPaymentDisplay');
});