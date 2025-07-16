<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use App\Models\Package;
use App\Models\referrals_settings;
use App\Service\TransactionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackagesController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function getPackages()
    {
        $packages = Package::where('active', 1)->get();
        return response()->json([
            'status' => true,
            'data' => $packages,
        ]);
    }


    public function BuyPackage(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'package_id' => 'required|exists:package,id',
        ]);

        $package = Package::find($validatedData['package_id']);
        $user = $request->user();
        $packageName = $package->name;

        if ($user->is_active == 0){
            return response()->json([
                'status' => false,
                'message' => 'Your account is not activated yet.',
            ]);
        }

        if($user->is_block == 1){
            return response()->json([
                'status' => false,
                'message' => 'Sorry, you cannot make a transaction because it is blocked'
            ],401);
        }

        if($package->stock <= $package->total_sell){
            return response()->json([
                'status' => false,
                'message' => 'This BizNode is Stock out'
            ]);
        }

        if ($user->wallet < $package->price) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient funds',
            ]);
        }

        DB::beginTransaction();

        try {
            $user->wallet -= $package->price;
            $user->save();
            $this->transactionService->addNewTransaction(
                $user->id,
                $package->price,
                "package_purchased",
                "-",
                "$packageName Package Purchased",
                "Completed",
                "USDT"
            );

            Investor::create([
                'user_id' => $user->id,
                'package_name' => $packageName,
                'return_type' => $package->return_type,
                'package_id' => $validatedData['package_id'],
                'investment' => $package->price,
                'duration' => $package->duration ?? null,
                'total_due_day' => $package->duration,
                'start_date' => now(),
                'next_cron' => now()->addDay(),
                'last_cron' => now(),
            ]);

            //$invest_level = referrals_settings::first();

            DB::commit();

            //level 1 bonus function here
            $level1 = $user->referredBy()->first();
            if($level1){
                $bonus = $package->price * 6 / 100;
                $bonus = $bonus/0.02;
                if ($level1->is_active){
                    $level1->increment('profit_wallet', $bonus);
                    $level1->save();
                    $this->transactionService->addNewTransaction(
                        "$level1->id",
                        "$bonus",
                        "referral_commission",
                        "+",
                        "Level 1 Referral From $user->name",
                    );
                }

                // Level 2 Logic
                $level2 = $level1->referredBy()->first();
                if($level2){
                    $bonus = $package->price * 2 / 100;
                    $bonus = $bonus/0.02;
                    if ($level2->is_active){
                        $level2->increment('profit_wallet', $bonus);
                        $level2->save();
                        $this->transactionService->addNewTransaction(
                            "$level2->id",
                            "$bonus",
                            "referral_commission",
                            "+",
                            "Level 2 Referral From $level2->name"
                        );
                    }
                }
            }

            Cache::forget('admin_dashboard_data');
            Cache::forget('packages_active_page_1');
            Cache::forget('packages_inactive_page_1');

            $package->total_sell++;
            $package->save();
            return response()->json([
                'status' => true,
                'message' => 'Package purchased successfully',
                'wallet_balance' => $user->wallet,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong! ' . $e->getMessage(),
            ], 500);
        }
    }

    public function InvestHistory(Request $request): JsonResponse{
        $user = $request->user();
        $investorData = Investor::where('user_id', $user->id)
            ->join('package', 'investors.package_id', '=', 'package.id')
            ->select('investors.*', 'package.interest_rate')
            ->paginate(10);
        $investorData->getCollection()->transform(function ($item) {
            $item->daily_roi = ($item->interest_rate * $item->investment) / 100;
            return $item;
        });
        return response()->json([
            'status' => true,
            'data' => $investorData->items(),
            'total' => $investorData->total(),
            'current_page' => $investorData->currentPage(),
            'last_page' => $investorData->lastPage(),

        ]);
    }

    public function cancelInvest(Request $request): JsonResponse
    {
        $user = $request->user();
        $validatedData = $request->validate([
            'id' => 'required|exists:investors,id'
        ]);

        $invest = Investor::where('id', $validatedData['id'])->where('user_id', $user->id)->where('status', 1)->first();
        if($invest){
            $invest->status = 0;
            $invest->save();
            $user->wallet += $invest->investment;
            $user->save();
            $this->transactionService->addNewTransaction(
                $user->id,
                $invest->investment,
                "package_purchased",
                "+",
                "$invest->package_name Package Cancelled"
            );
            return response()->json([
                'status' => true,
                'message' => 'Package cancelled successfully',
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Investor not found',
            ],422);
        }
    }

}
