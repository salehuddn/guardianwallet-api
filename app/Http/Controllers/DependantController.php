<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Services\SpendingService;

class DependantController extends Controller
{
    public function dependantProfile(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('dependant')) {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized: You are not a dependant.'
            ], 401);
        }

        $dependant = User::where('id', $user->id)->first();

        $dependantProfile = [
            'id' => $dependant->id,
            'name' => $dependant->name,
            'email' => $dependant->email,
            'dob' => $dependant->dob,
            'phone' => $dependant->phone,
            'role' => $dependant->roles->pluck('name')->first(),
            'spending_limit' => $dependant->spendingLimit ? $dependant->spendingLimit->limit : 'N/A',
        ];

        return response()->json([
            'code' => 200,
            'user' => $dependantProfile,
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->only(['name', 'password']);
        $data = array_filter($data);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json([
            'code' => 200,
            'message' => 'Profile updated successfully'
        ], 200);
    }

    public function wallet(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet;

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'wallet' => [
                'id' => $wallet->id,
                'balance' => $wallet->balance,
                'status' => $wallet->status,
            ]
        ], 200);
    }

    public function transactionHistory (Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('dependant')) {
            $transactions = $user->transactions()->with('transactionType')->latest()->get();

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'transactions' => $transactions
            ], 200);
        } else {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized: You are not a dependant.'
            ], 401);
        }
    }

    public function scanQr (Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('dependant')) {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized: You are not a dependant.'
            ], 401);
        }

        if (!$request->qr_content) {
            return response()->json([
                'code' => '400',
                'message' => 'qr_content is required'
            ], 400);
        }

        $qrContent = explode('-', $request->qr_content);
        $merchant = Merchant::find($qrContent[0]);

        return response()->json([
            'code' => '200',
            'message' => 'success',
            'merchant' => $merchant
        ], 200);
    }

    public function transferFund (Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('dependant')) {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized: You are not a dependant.'
            ], 401);
        }

        $data = $request->all();

        if (!$data['amount'] || !$data['merchant_id']) {
            return response()->json([
                'code' => '400',
                'message' => 'amount and recipient_id are required'
            ], 400);
        }

        $merchant = Merchant::find($data['merchant_id']);

        // check user spending limit
        $limitCheckResult = SpendingService::checkLimit($user, $data['amount']);
        if ($limitCheckResult) {
            return response()->json($limitCheckResult, 400);
        }

        // create transaction
        $transaction = $user->transactions()->create([
            'amount' => $request->amount,
            'transaction_type_id' => 2,
            'status' => 'pending',
            'narration' => $merchant->type->name,
            'pending_at' => now()
        ]);

        if (!$merchant) {
            return response()->json([
                'code' => '404',
                'message' => 'Merchant not found'
            ], 404);
        }

        //update user & merchant wallet
        $user->wallet->decrement('balance', $data['amount']);
        $merchant->wallet->increment('balance', $data['amount']);

        //update transaction
        $transaction->update([
            'status' => 'success',
            'completed_at' => now()
        ]);

        return response()->json([
            'code' => '200',
            'message' => 'Fund transferred successfully',
            'transaction' => $transaction
        ], 200);
    }
}
