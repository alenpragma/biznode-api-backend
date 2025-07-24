<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\UserWalletData;
use App\Service\TransactionService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $wallet = UserWalletData::where('user_id', $user->id)
            ->select('wallet_address', 'meta')
            ->first();

        if (!$wallet || empty($wallet->wallet_address)) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet address not found for user.'
            ], 404);
        }

        try {
            $response = $client->post('https://evm.blockmaster.info/api/deposit', [
                'json' => [
                    'type' => 'token',
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

            $responseData = json_decode($response->getBody(), true);

            if (!is_array($responseData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid response format',
                ]);
            }

            if ($responseData['status'] === false) {
                return response()->json([
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Unknown error',
                ]);
            }

            DB::beginTransaction();

            $txHash = $responseData['tx_hash'] ?? null;
            $amount = $responseData['amount'] ?? null;
            return [
                $responseData['tx_hash'],
                $amount = $responseData['amount']

            ];

            $alreadyExists = Deposit::where('transaction_id', $txHash)->exists();

            if (!$alreadyExists && $txHash !== null) {
                Deposit::create([
                    'transaction_id' => $txHash,
                    'amount' => $amount,
                    'user_id' => $user->id,
                ]);
                $user->wallet += $amount;
                $user->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Deposit check completed successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

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
