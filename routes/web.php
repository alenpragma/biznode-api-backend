<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CronController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\admin\KycController;
use App\Http\Controllers\admin\PlansController;
use App\Http\Controllers\admin\UsersController;
use App\Http\Controllers\admin\DepositController;
use App\Http\Controllers\admin\WithdrawController;

use App\Http\Controllers\admin\TransactionsController;
use App\Http\Controllers\admin\AdminDashboardController;
use App\Http\Controllers\admin\GeneralSettingsController;
use App\Http\Controllers\admin\WithdrawSettingsController;
use App\Http\Controllers\admin\ReferralsSettingsController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard',[AdminDashboardController::class,'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::get('admin-withdraw', [CronController::class, 'UserWalletToAdminWallet']);

Route::middleware('auth')->group(function () {

    //all user
    Route::get('users', [UsersController::class, 'index'])->name('users.index');
    Route::post('users/update', [UsersController::class, 'update'])->name('users.update');
    Route::resource('all-plan', PlansController::class);
    Route::resource('withdraw', WithdrawController::class);
    Route::resource('transactions', TransactionsController::class);
    Route::resource('kyc', KycController::class);
    Route::get('cron', [CronController::class, 'view'])->name('cron');


    //holiday
    Route::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');
    Route::post('/holidays/toggle', [HolidayController::class, 'toggleStatus'])->name('holidays.toggle');


    Route::get('/withdraws/settings', [WithdrawSettingsController::class, 'index'])->name('withdraw.settings');
    Route::post('/withdraws/settings', [WithdrawSettingsController::class, 'update'])->name('admin.withdraw.settings.update');


    Route::get('ReferralsSettings',[ReferralsSettingsController::class,'index'])->name('ReferralsSettings');
    Route::post('ReferralsSettings',[ReferralsSettingsController::class,'update'])->name('admin.referral.settings.update');


    Route::get('/plan/export', [PlansController::class, 'export'])->name('all-plan.export');
    Route::post('/plan/import', [PlansController::class, 'import'])->name('all-plan.import');

    //deposit
    Route::resource('/deposit', DepositController::class);

    // general Settings

    Route::get('general-settings', [GeneralSettingsController::class, 'index'])->name('admin.general.settings');
    Route::post('general-settings', [GeneralSettingsController::class, 'update'])->name('admin.general.settings.update');

});

require __DIR__.'/auth.php';
