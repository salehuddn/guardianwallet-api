<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DependantController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\MerchantController;
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

    //stripe
    Route::get('topup-wallet', [GuardianController::class, 'topupWallet'])->name('topup-wallet');
    Route::get('success', [GuardianController::class, 'success'])->name('topup.success');
    Route::get('cancel', [GuardianController::class, 'cancel'])->name('topup.cancel');

    //merchants
    Route::get('merchants', [MerchantController::class, 'listOfMerchants']);
    Route::get('qr-code', [MerchantController::class, 'showQr']);
});

Route::middleware(['auth:sanctum'])->prefix('v1/secured')->group(function () {  
    //auth
    Route::post('logout', [AuthController::class, 'logout']);
    
    //guardian
    Route::prefix('guardian')->group(function () {
        Route::get('profile', [GuardianController::class, 'guardianProfile']);
        Route::post('update-profile', [GuardianController::class, 'updateProfile']);
        Route::post('create-dependant', [GuardianController::class, 'registerDependant']);
        Route::get('dependants', [GuardianController::class, 'dependants']);
        Route::post('topup-wallet', [GuardianController::class, 'topupWallet'])->name('topup-wallet');
        Route::get('transaction-history', [GuardianController::class, 'transactionHistory']);
        Route::get('wallet', [GuardianController::class, 'wallet']);
        Route::post('transfer-fund', [GuardianController::class, 'transferFund']);
        
    });

    //dependent
    Route::prefix('dependant')->group(function () {
        Route::get('profile', [DependantController::class, 'dependantProfile']);
        Route::post('update-profile', [DependantController::class, 'updateProfile']);
        Route::get('wallet', [DependantController::class, 'wallet']);
        Route::get('transaction-history', [DependantController::class, 'transactionHistory']);
        Route::post('scan-qr', [DependantController::class, 'scanQr']);
        Route::post('transfer-fund', [DependantController::class, 'transferFund']);
    });
});
