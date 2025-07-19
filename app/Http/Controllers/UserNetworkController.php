<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserNetworkController extends Controller
{
    public function index(Request $request){
        $user = $request->user();
        return response()->json([
            'status' => true,
            'data' => [
                'Level1' => [
                    'total' => $user->referrals()->count(),
                    'totalInvestment' => $user->totalLevel1InvestmentAmount(),
                ],
                'Level2' => [
                    'total' => $user->countLevel2Users(),
                    'totalInvestment' => $user->totalTeamInvestment(),
                ]
            ]
        ]);
    }
}
