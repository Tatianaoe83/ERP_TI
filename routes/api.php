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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas para la cascada de tipos de tickets
Route::get('/tertipos-by-subtipo', [App\Http\Controllers\TicketsController::class, 'getTertiposBySubtipo']);
Route::get('/subtipos-by-tipo', [App\Http\Controllers\TicketsController::class, 'getSubtiposByTipo']);
Route::get('/tipos', [App\Http\Controllers\TicketsController::class, 'getTipos']);