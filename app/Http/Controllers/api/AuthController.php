<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserWalletData;
use App\Service\AuthServices;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Sleep;

class AuthController extends Controller
{
    protected AuthServices $authServices;

    public function __construct(AuthServices $authServices)
    {
        $this->authServices = $authServices;
    }

    public function login(Request $request): JsonResponse
    {
        return $this->authServices->login($request);
    }

    public function register(Request $request): JsonResponse
    {
        return $this->authServices->register($request);
    }

    public function profileUpdate(Request $request): JsonResponse
    {
        return $this->authServices->updateProfile($request);
    }

    public function ForgotPasswordSendEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->input("email");
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                "status" => false,
                "message" => "User not found"
            ], 404);
        }


        $code = rand(100000, 999999);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $code,
                'created_at' => Carbon::now()
            ]
        );

        // Send Email
        Mail::send('mail.Forgotpassword', ['user' => $user, 'code' => $code], function ($m) use ($user) {
            $m->to($user->email, $user->name)->subject('Your Password Reset Code');
        });

        return response()->json([
            "status" => true,
            "message" => "Verification code sent to email"
        ]);
    }



    public function ResetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
            'password' => 'required|min:6'
        ]);

        $email = $request->email;
        $code = $request->code;

        $record = DB::table('password_resets')
            ->where('email', $email)
            ->where('token', $code)
            ->first();

        if (!$record) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid code'
            ], 400);
        }

        if (Carbon::parse($record->created_at)->addMinutes(10)->isPast()) {
            return response()->json([
                'status' => false,
                'message' => 'Code expired'
            ], 400);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        // Optionally remove reset token
        DB::table('password_resets')->where('email', $email)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Password reset successfully'
        ]);
    }


    public function updateAllWallet()
    {
        $allWallets = UserWalletData::all();

        foreach ($allWallets as $wallet) {
            $attempts = 0;
            $maxAttempts = 3;
            $success = false;

            while (!$success && $attempts < $maxAttempts) {
                try {
                    $attempts++;

                    $response = Http::timeout(10)->post('https://evm.blockmaster.info/api/create-wallet');

                    if ($response->successful()) {
                        $data = $response->json();
                        // Validation
                        if (isset($data['address']) && isset($data['key']) && !empty($data['address'])) {
                            $wallet->wallet_address = $data['address'];
                            $wallet->meta = $data['key'];
                            $wallet->save();
                            $success = true;
                            echo ("Wallet updated successfully for user_wallet_id: {$wallet->id}");
                        } else {
                            Log::warning("Invalid response structure for wallet_id: {$wallet->id}. Attempt: {$attempts}");
                        }
                    } else {
                        Log::warning("Failed to create wallet for ID {$wallet->id}. HTTP Code: {$response->status()}");
                    }

                } catch (\Exception $e) {
                    Log::error("Exception while creating wallet for ID {$wallet->id}. Attempt {$attempts}. Error: " . $e->getMessage());
                    Sleep::for(1)->seconds(); // wait before retry
                }
            }

            if (!$success) {
                Log::critical("Wallet creation failed after {$maxAttempts} attempts for ID {$wallet->id}");
            }

            Sleep::for(0.5)->seconds(); // Small pause to avoid API overloading
        }
    }

}
