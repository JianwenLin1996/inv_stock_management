<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// XSS, Auth
Route::group([
    'middleware' => ['api', 'XssSanitizer']
], function ($router) {
    Route::post('signup', [AuthController::class, 'signup']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);

    Route::get('items', [ItemController::class, 'index']);
    Route::post('items', [ItemController::class, 'store']);
    Route::get('items/{id}', [ItemController::class, 'show']);
    Route::post('items/{id}', [ItemController::class, 'update']);
    Route::delete('items/{id}', [ItemController::class, 'destroy']);
    Route::get('items/{id}/cost', [ItemController::class, 'showCost']);
    
    Route::get('transactions', [TransactionController::class, 'index']);
    Route::post('transactions', [TransactionController::class, 'store']);
    Route::get('transactions/{id}', [TransactionController::class, 'show']);
    Route::post('transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('transactions/{id}', [TransactionController::class, 'destroy']);
});


