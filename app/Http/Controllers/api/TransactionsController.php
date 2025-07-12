<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Transactions;
use App\Models\User;
use App\Models\withdraw_settings;
use App\Service\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionsController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function transactions(Request $request)
    {
        $keyword = $request->get('keyword');
        if ($keyword) {
            $transactions = Transactions::where('user_id', $request->user()->id)->where('remark', '=', $keyword)->orderBy('id', 'desc')->paginate(10);
            return response()->json([
                'status' => true,
                'data' => $transactions->items(),
                'total' => $transactions->total(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
            ]);
        }
        $transactions = Transactions::where('user_id', $request->user()->id)->orderBy('id', 'desc')->paginate(10);
        return response()->json([
            'status' => true,
            'data' => $transactions->items(),
            'total' => $transactions->total(),
            'last_page' => $transactions->lastPage(),
            'current_page' => $transactions->currentPage(),
            'per_page' => $transactions->perPage(),
            'from' => $transactions->firstItem(),
        ]);
    }



    public function withdraw(Request $request)
    {
        $withdrawSettings = withdraw_settings::first();

        if($withdrawSettings->status == 0){
            return response()->json([
                'status' => false,
                'message' => 'Withdrawals are temporarily disabled. Please contact support'
            ]);
        }

        if (!$withdrawSettings) {
            return back()->with('error', 'Withdraw settings not found.');
        }

        $min = $withdrawSettings->min_withdraw;
        $max = $withdrawSettings->max_withdraw;
        $charge = $withdrawSettings->charge;

        $validatedData = $request->validate([
            'amount' => ['required', 'numeric', "min:$min", "max:$max"],
            'wallet' => ['required', 'string', 'min:10', 'max:70'],
        ]);

        $user = $request->user();
        $amount = $validatedData['amount'];
        $amount = $amount + ($amount * $charge / 100);
        $wallet = $validatedData['wallet'];



        if ($user->wallet < $amount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance',
            ], 400);
        } else {
            $this->transactionService->addNewTransaction(
                "$user->id",
                "$amount",
                "withdrawal",
                "-",
                "$wallet", 'Pending');
            $user->wallet -= $amount;
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'Your withdrawal request has been received and is currently pending.',
                'wallet_balance' => $user->wallet,
            ]);
        }
    }

}
