<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::apiResources([
    'categories' => App\Http\Controllers\API\CategoryController::class,
    'courses' => App\Http\Controllers\API\CourseController::class,
]);

Route::group(['name' => 'auth.'], function () {
    Route::post('/login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
    Route::post('/register', [App\Http\Controllers\Auth\RegisteredUserController::class, 'store']);
    Route::post('/logout', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'logout']);
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::apiResources([
        'courses-progress' => App\Http\Controllers\API\CourseProgressController::class,
    ]);
});
