<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Transactions;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $dashboardData = Cache::remember('admin_dashboard_data', now()->hour(1), function () {
            return [

                // user
                'totalUser' => User::where('role', 'user')->count(),
                'activeUser' => User::where('is_active', 1)->where('role', 'user')->count(),
                'blockUser' => User::where('is_block', 1)->where('role', 'user')->count(),
                'newUser' => User::where('created_at', '>=', now()->startOfDay()->addHours(5))->where('role', 'user')->count(),

                // deposit
                'totalDeposits' => Transactions::where('remark', 'deposit')->where('status', 'Completed')->count(),
                'rejectedDeposits' => Transactions::where('remark', 'deposit')->where('status', 'rejected')->count(),
                'pendingDeposits' => Transactions::where('remark', 'deposit')->where('status', 'pending')->count(),
                'todayDeposits' => Transactions::where('remark', 'deposit')->where('status', 'Completed')->whereDate('created_at', today())->count(),

                // withdrawal
                'totalWithdrawals' => Transactions::where('remark', 'withdrawal')->where('status', 'Completed')->sum('amount'),
                'pendingWithdrawals' => Transactions::where('remark', 'withdrawal')->where('status', 'pending')->count(),
                'rejectedWithdrawals' => Transactions::where('remark', 'withdrawal')->where('status', 'rejected')->count(),
            ];
        });

        return view('admin.dashboard', compact('dashboardData'));
    }
}
