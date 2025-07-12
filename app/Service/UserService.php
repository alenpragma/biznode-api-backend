<?php

namespace App\Service;


use App\Models\Investor;
use App\Models\kyc;
use App\Models\Transactions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserService
{
    public function UserProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $teamInvest = (float) $user->totalTeamInvestment();
        $directRefer = $user->referrals()->count();
        $totalTeam = $user->totalTeamMembersCount();
        $roi = (float) Transactions::where('user_id', $user->id)->where('remark','interest')->sum('amount') ?? '0';
        $totalInvestment = (float) Investor::where('user_id', $user->id)->sum('investment') ?? '0';
        $totalWithdraw = (float) Transactions::where('user_id', $user->id)->where('remark','withdrawal')->sum('amount');
        $totalTransfer = (float) Transactions::where('user_id', $user->id)->where('remark','transfer')->sum('amount');
        $totalDeposit = (float) Transactions::where('user_id', $user->id)
            ->where('remark', 'deposit')
            ->whereIn('status', ['Completed', 'Paid'])
            ->sum('amount');
        $totalEarning = (float) Transactions::where('user_id', $user->id)->whereIn('remark', ['referral_commission', 'interest'])->sum('amount');
        $totalReferBonus = (float) Transactions::where('user_id', $user->id)->where('remark','referral_commission')->sum('amount');

        $total_active_team = $user->referrals()->where('is_active',1)->count();
        $total_inactive_team = $user->referrals()->where('is_active',0)->count();
        return response()->json([
            'status' => true,
            'message' => 'User Profile Retrieved Successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'image' => $user->image,
                    'birthday' => $user->birthday,
                    'nid_or_passport' => $user->nid_or_passport,
                    'address' => $user->address,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'refer_code' => $user->refer_code,
                    'refer_by' => $user->refer_by,
                    'is_active' => $user->is_active,
                    'is_block' => $user->is_block,
                    'kyc_status' => $user->kyc_status,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'usdt_wallet' => $user->wallet,
                'bizt_wallet' => $user->profit_wallet,
                'teamInvest' => $teamInvest,
                'directRefer' => $directRefer,
                'totalTeam' => $totalTeam,
                'total_active_team' => $total_active_team,
                'total_inactive_team' => $total_inactive_team,
                'reward' => $roi,
                'totalInvestment' => $totalInvestment,
                'totalWithdraw' => $totalWithdraw,
                'totalTransfer' => $totalTransfer,
                'totalDeposit' => $totalDeposit,
                'totalEarning' => $totalEarning,
                'totalReferBonus' => $totalReferBonus,
            ]
        ]);
    }

    public function UserKyc(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'front_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'selfie_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(), // or customize a fixed message
            ]);
        }

        $frontImgPath = $request->file('front_image')->store('kyc_images', 'public');
        $selfieImgPath = $request->file('selfie_image')->store('kyc_images', 'public');

        $frontImgUrl = asset('public/storage/' . $frontImgPath);
        $selfieImgUrl = asset('public/storage/' . $selfieImgPath);

        $existingKyc = Kyc::where('user_id', $user->id)->latest()->first();

        if ($existingKyc) {
            if ($existingKyc->status === 'approved') {
                return response()->json([
                    'status' => true,
                    'message' => 'Your KYC Status is Already Approved.',
                ]);
            }

            if ($existingKyc->status === 'pending') {
                return response()->json([
                    'status' => true,
                    'message' => 'Your KYC is Currently Under Review.',
                ]);
            }


            if ($existingKyc->status === 'rejected') {
                // Delete old images if they exist
                foreach (['ind_front', 'ind_back', 'selfie'] as $field) {
                    if (!empty($existingKyc->$field)) {
                        $path = str_replace(asset('storage') . '/', '', $existingKyc->$field);
                        Storage::disk('public')->delete($path);
                    }
                }

                // Update rejected KYC with new images
                $existingKyc->update([
                    'nid_front' => $frontImgUrl,
                    'selfie' => $selfieImgUrl,
                    'name' => $user->name,
                    'status' => 'pending',
                ]);
                Cache::flush();
                return response()->json([
                    'status' => true,
                    'message' => 'Your Rejected KYC Has Been Resubmitted and is Now Pending.',
                ]);
            }

        }

        // Create new KYC record if none exists
        Kyc::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'nid_front' => $frontImgUrl,
            'selfie' => $selfieImgUrl,
            'status' => 'pending',
        ]);

        $user->kyc_status = 3;
        $user->save();

        Cache::flush();

        return response()->json([
            'status' => true,
            'message' => 'KYC Submitted Successfully. Awaiting Verification.',
        ]);
    }

}
