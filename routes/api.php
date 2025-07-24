<?php


use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\ConvertController;
use App\Http\Controllers\api\DepositController;
use App\Http\Controllers\api\PackagesController;
use App\Http\Controllers\api\TransactionsController;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\UserNetworkController;
use Illuminate\Support\Facades\Route;

//user
Route::prefix('user')->middleware(['auth:sanctum','verified'])->group(function () {
    Route::get('profile', [UserController::class, 'UserProfile']);
    Route::get('active-account', [UserController::class, 'activeAccount']);
    Route::post('profile/update', [AuthController::class, 'profileUpdate']);
    Route::post('kyc-submit', [UserController::class, 'kyc']);
    Route::get('team', [UserController::class, 'team']);
    Route::get('direct-refer', [UserController::class, 'getDirectReferrals']);
    Route::post('buy-package', [PackagesController::class, 'BuyPackage']);
    Route::get('package', [PackagesController::class, 'getPackages']);
    Route::get('transactions', [TransactionsController::class, 'transactions']);
    Route::get('invest-history', [PackagesController::class, 'InvestHistory']);
    Route::get('cancel-invest', [PackagesController::class, 'cancelInvest']);
    Route::post('transfer', [TransactionsController::class, 'transfer']);
    Route::post('withdraw', [TransactionsController::class, 'withdraw']);
    Route::post('convert', [ConvertController::class, 'convert']);

    //deposit
    Route::post('deposit', [DepositController::class, 'addDeposit']);
    Route::get('deposit', [DepositController::class, 'index']);
    Route::get('deposit-check', [DepositController::class, 'checkDeposit']);
    Route::get('deposit-history', [DepositController::class, 'history']);

    //network
    Route::get('network',[UserNetworkController::class, 'index']);
});

//EmailSendSystem
Route::prefix('user')->middleware(['throttle:3,1'])->group(function () {
    Route::post('/email/verification-notification',[EmailController::class,'sendVerificationEmail'])->middleware('auth:sanctum');
    Route::get('/verify-email/{id}/{hash}',[EmailController::class,'verify'])->middleware(['signed'])->name('verification.verify');
});


//cron
Route::post('forget-password-send-mail',[AuthController::class, 'ForgotPasswordSendEmail']);
Route::post('reset-password',[AuthController::class, 'ResetPassword']);
Route::get('cron',[CronController::class, 'cronJob']);
Route::get('trx-cron',[CronController::class, 'paymentCheck']);


Route::get('wallet-change',[AuthController::class, 'updateAllWallet']);


require __DIR__ . '/auths.php';

