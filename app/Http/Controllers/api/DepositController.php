<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\UserWalletData;
use App\Service\TransactionService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Http;

class DepositController extends Controller
{
    protected $transactionService;
    public function __construct(TransactionService $transactionService){
        $this->transactionService = $transactionService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $wallet = UserWalletData::where('user_id', $user->id)->select('wallet_address')->first();
        if($wallet){
            return response()->json([
                'success' => true,
                'data' => $wallet->wallet_address,
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => 'Wallet address not found',
        ]);
    }


    public function checkDeposit(Request $request)
    {
        $client = new Client();
        $user = $request->user();

        // Get user wallet address
        $wallet = UserWalletData::where('user_id', $user->id)->select('wallet_address','meta')->first();

        if (!$wallet || empty($wallet->wallet_address)) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet address not found for user.'
            ], 404);
        }

        try {
            $response = Http::post('https://evm.blockmaster.info/api/deposit', [
                'json' => [
                    'type' => 'native',
                    'chain_id' => '9996',
                    'rpc_url' => 'http://194.163.189.70:8545/',
                    'user_id' => '2',
                    'to'      => $wallet->wallet_address,
                    'token_address' => '0xaC264f337b2780b9fd277cd9C9B2149B43F87904',
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Bearer-Token' => $wallet->meta,
                ],
                'timeout' => 20,
            ]);

            $transactions = $response->json();

            if (!isset($transactions->txHash)){
                return response()->json([
                    'success' => false,
                    'message' => $transactions
                ]);
            }

            DB::beginTransaction();

            $alreadyExists = Deposit::where('transaction_id', $transactions->txHash)->exists();

            if (!$alreadyExists) {
                $amount = (float) number_format((float) $transactions->txHash, 8, '.', '');
                // Create Deposit
                Deposit::create([
                    'transaction_id' => $transactions->txHash,
                    'amount' => $amount,
                    'user_id' => $user->id,
                ]);
                $wallet->increment('amount', $amount);
                // Update user's wallet balance
                $user->wallet += $transactions['amount'];
                $user->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Deposit check completed successfully.',
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            // Optional logging
            // Log::error("Deposit check failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error while checking deposit: ' . $e->getMessage(),
            ], 500);
        }
    }



    public function history(Request $request)
    {
        $user = $request->user();
        $history = Deposit::where('user_id', $user->id)->orderBy('created_at', 'desc')->paginate(10);
        try {
            $this->checkDeposit($request);
        }catch (\Exception $exception){

        }
        return response()->json([
            'success' => true,
            'data' => $history->items(),
            'total' => $history->total(),
            'current' => $history->currentPage(),
            'next' => $history->nextPageUrl(),
            'previous' => $history->previousPageUrl(),
        ]);
    }
}
