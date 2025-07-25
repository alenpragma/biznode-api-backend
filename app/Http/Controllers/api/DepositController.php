<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\User;
use App\Models\UserWalletData;
use App\Service\TransactionService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepositController extends Controller
{
    protected TransactionService $transactionService;
    public function __construct(TransactionService $transactionService){
        $this->transactionService = $transactionService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $wallet = UserWalletData::where('user_id', $user->id)->select('wallet_address')->first();
        if($wallet){
           $checkJob = DB::table('check_deposit_job')->where('userId', $user->id)->where('job_status', 1)->first();
           if(!$checkJob){
               DB::table('check_deposit_job')->insert([
                   'userId' => $user->id,
                   'job_created_at' => Carbon::now()
               ]);
           }
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


    public function checkDeposit(): JsonResponse
    {
        $client = new Client();
        $jobs = DB::table('check_deposit_job')->get();

        if ($jobs->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No deposit jobs found.',
                'data' => [],
            ]);
        }

        $successCount = 0;
        $errors = [];

        foreach ($jobs as $job) {
            $jobCreateTime = Carbon::parse($job->job_created_at); // Updated naming convention

            // Delete old jobs (>= 5 minutes)
            if ($jobCreateTime->diffInMinutes(Carbon::now()) >= 5) {
                DB::table('check_deposit_job')->where('userId', $job->userId)->delete();
                continue;
            }

            $user = User::find($job->userId);
            if (!$user) {
                $errors[] = "User not found with ID: {$job->userId}";
                continue;
            }

            $wallet = UserWalletData::where('user_id', $user->id)
                ->select('wallet_address', 'meta')
                ->first();

            if (!$wallet || empty($wallet->wallet_address)) {
                $errors[] = "Wallet address not found for user ID: {$user->id}";
                continue;
            }

            try {
                $response = $client->post(env('DEPOSIT_URL'), [
                    'json' => [
                        'type' => 'token',
                        'chain_id' => '56',
                        'user_id' => '2',
                        'to' => $wallet->wallet_address,
                        'token_address' => '0x55d398326f99059fF775485246999027B3197955',
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                        'Bearer-Token' => $wallet->meta
                    ],
                    'timeout' => 20,
                ]);

                $responseData = json_decode($response->getBody(), true);

                if (!is_array($responseData)) {
                    $errors[] = "response: $responseData";
                    continue;
                }

                if (isset($responseData['status']) && $responseData['status'] === false) {
                    $errors[] = $responseData['message'] ?? "Unknown error for user ID:";
                    continue;
                }

                $txHash = $responseData['txHash'] ?? null;
                $amount = $responseData['amount'] ?? null;

                if ($txHash === null || $amount === null) {
                    $errors[] = "Missing txHash or amount for user ID: {$user->id}";
                    continue;
                }

                if (Deposit::where('transaction_id', $txHash)->exists()) {
                    $errors[] = "Duplicate transaction for txHash: {$txHash}";
                    continue;
                }

                DB::beginTransaction();

                Deposit::create([
                    'transaction_id' => $txHash,
                    'amount' => $amount,
                    'user_id' => $user->id,
                ]);

                $user->wallet += $amount;
                $user->save();

                DB::table('check_deposit_job')->where('userId', $job->userId)->delete();
                DB::commit();
                sleep(2);
                $successCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = "Exception for user ID {$user->id}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$successCount} job(s) processed successfully.",
            'errors' => $errors,
        ]);
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
