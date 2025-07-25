<?php

namespace App\Service;

use App\Models\User;
use App\Models\UserWalletData;
use GuzzleHttp\Client;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AuthServices
{
    public function login(Request $request): JsonResponse{
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ]);
        }
        $user = User::where('email', $request->input('email'))->first();
        Cache::forget('admin_dashboard_data');
        if($user->email_verified_at == null){
            return response()->json([
                'success' => false,
                'message' => 'Email is not verified Please Check Your Email and verify your Email'
            ]);
        }
        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'email' => 'The provided credentials are incorrect.',
                ]
            ]);
        }else{
            if ($user->is_block == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry, your account is blocked. Please contact admin.',
                ]);
            }
            $token = $user->createToken('my-app-token')->plainTextToken;
            return response()->json([
                'success' => true,
                'message' => 'User logged in successfully',
                'data' => [
                    'token' => $token,
                ]
            ]);
        }
    }


    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'mobile'    => 'required|string|max:15|min:10',
            'referCode' => 'nullable|string|max:8',
            'password'  => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Check referrer
            $referBy = null;
            if ($request->filled('referCode')) {
                $referBy = User::where('refer_code', $request->input('referCode'))->first();
                if (!$referBy) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Referral code not found.',
                    ], 404);
                }
            }

            // External API call to generate wallet
            $response = Http::post('https://evm.blockmaster.info/api/create-wallet');

            $walletData = $response->json();

            if (!isset($walletData['address']) || !isset($walletData['key'])) {
                throw new \Exception("Invalid response from wallet service.");
            }

            // Create user
            $user = User::create([
                'name'      => $request->input('name'),
                'email'     => $request->input('email'),
                'mobile'    => $request->input('mobile'),
                'refer_by'  => $referBy?->id,
                'password'  => Hash::make($request->input('password')),
            ]);

            // Store wallet
            UserWalletData::create([
                'user_id'        => $user->id,
                'meta'           => $walletData['key'],
                'wallet_address' => $walletData['address'],
                'currency'       => 'USD',
            ]);

            // Send verification notification
            $user->notify(new VerifyEmail());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Account created successfully. Please verify your email.',
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Registration failed.',
                'error'   => str_contains($e->getMessage(), 'https://web3.blockmaster.info/api/create-address') === true ? "Wallet Create Failed Server Busy Please Try again" : $e->getMessage(),
            ], 500);
        }
    }


    public function updateProfile(Request $request): JsonResponse
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'mobile'   => 'required|string|max:15|min:10',
            'address'  => 'nullable|string|max:255',
            'image'    => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'birthday' => 'nullable|date',
            'nid_or_passport' => 'nullable|string|max:15|min:10',
        ]);

        // Return errors if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Update basic fields
        $user->name = $request->input('name');
        $user->mobile = $request->mobile;
        $user->address = $request->address;
        $user->birthday = $request->birthday;
        $user->nid_or_passport = $request->nid_or_passport;

        // Handle image upload
        if ($request->hasFile('image')) {
            // Optionally delete old image if exists
            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }

            $imagePath = $request->file('image')->store('profile_images', 'public');
            $user->image = $imagePath;
        }

        // Save user data
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

}
