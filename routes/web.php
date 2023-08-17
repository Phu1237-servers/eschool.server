<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/one', [App\Http\Controllers\OneDriveController::class, 'index']);
Route::get('/access_token_response', [App\Http\Controllers\OneDriveController::class, 'access_token_response'])->name('redirect_uri');
