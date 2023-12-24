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


Route::group(['middleware' => 'onedrive'], function () {
    Route::group(['prefix' => 'install', 'as' => 'install.'], function () {
        // Route::get('/', [App\Http\Controllers\InstallController::class, 'index'])->name('index');
        Route::get('/', [App\Http\Controllers\InstallController::class, 'install'])->name('submit');
    });
	Route::get('/one', [App\Http\Controllers\OneDriveController::class, 'index'])->name('one');
	Route::get('revoke', [App\Http\Controllers\OneDriveController::class, 'revoke']);
});
Route::get('flush', [App\Http\Controllers\OneDriveController::class, 'flush']);
Route::get('/access_token_response', [App\Http\Controllers\InstallController::class, 'access_token_response'])->name('redirect_uri');
Route::get('/console_access_token', [App\Http\Controllers\InstallController::class, 'console_access_token'])->name('console_redirect_uri');
