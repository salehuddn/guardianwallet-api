<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DependantController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\UserProfileController;

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

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Route::prefix('v1/public')->middleware('api_key')->group(function () {
//     Route::post('/register', [AuthController::class, 'register']);
//     Route::post('/login', [AuthController::class, 'login']);
//     Route::post('/deleteUser', [AuthController::class, 'deleteUser']);
// });

Route::post('/generateApiKey', [AuthController::class, 'generateApiKey']);

Route::prefix('v1/public')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('deleteUser', [AuthController::class, 'deleteUser']);
});

Route::middleware(['auth:sanctum'])->prefix('v1/secured')->group(function () {  
    //auth
    Route::post('logout', [AuthController::class, 'logout']);
    
    //guardian
    Route::prefix('guardian')->group(function () {
        Route::get('profile', [GuardianController::class, 'guardianProfile']);
        Route::post('create-dependant', [GuardianController::class, 'registerDependant']);
    });

    //dependent
    Route::prefix('dependent')->group(function () {
        Route::get('/profile', [DependantController::class, 'dependentProfile']);
    });
});