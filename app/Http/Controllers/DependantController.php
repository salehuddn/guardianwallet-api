<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Savings;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\SpendingService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Hash;
use App\Notifications\SpendingLimitNotification;

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
            'spending_limit' => $dependant->getSpendingLimit() ?? 'N/A'
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
            $transactions = $user->transactions()->with(['transactionType', 'merchant.type', 'savings'])->latest()->get();

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

        if (!$merchant) {
            return response()->json([
                'code' => '404',
                'message' => 'Merchant not found'
            ], 404);
        }

        // check if the user has a wallet and if the wallet balance is sufficient
        if (!$user->wallet || $user->wallet->balance == 0.00 || $user->wallet->balance < $data['amount']) {
            return response()->json([
                'code' => 400,
                'message' => 'Amount is higher than wallet balance or wallet is empty'
            ], 400);
        }

        // check user spending limit
        $limitCheckResult = SpendingService::checkLimit($user, $data['amount']);
        if ($limitCheckResult && $limitCheckResult['code'] === 200) {
            // send notification to dependant & guardian
            $title = 'Spending Limit Alert';
            $user->notify(new SpendingLimitNotification(
                "Spending Limit Exceeded",
                "You have exceeded your spending limit for the week"
            ));

            // notify the guardian
            if ($user->hasRole('dependant')) {
                $guardian = $user->guardians()->first();
                if ($guardian) {
                    $guardian->notify(new SpendingLimitNotification(
                        "User: {$user->name}",
                        "Your dependant {$user->name} has exceeded their spending limit for the week"
                    ));
                }
            }

            return response()->json($limitCheckResult, 400);
        }

        // create transaction
        $transaction = $user->transactions()->create([
            'amount' => $request->amount,
            'transaction_type_id' => TransactionService::getTransactionTypeIdBySlug("transfer-fund"),
            'merchant_id' => $merchant->id,
            'status' => 'pending',
            'narration' => $merchant->type->name,
            'pending_at' => now()
        ]);

        //update user & merchant wallet
        $user->wallet->decrement('balance', $data['amount']);
        $merchant->wallet->increment('balance', $data['amount']);

        //update transaction
        $transaction->update([
            'status' => 'success',
            'completed_at' => now()
        ]);

        // check if user has almost exceeded the limit
        $exceedLimit = SpendingService::hasAlmostExceededLimit($user);

        if ($exceedLimit && $exceedLimit['code'] === 200) {
            $title = 'Spending Limit Alert';
            $user->notify(new SpendingLimitNotification($title, $exceedLimit['message']));

            // notify the guardian
            if ($user->hasRole('dependant')) {
                $guardian = $user->guardians()->first();
                if ($guardian) {
                    $guardian->notify(new SpendingLimitNotification(
                        "Dependant: {$user->name}",
                        "Your dependant {$user->name} has spent more than 70% of their spending limit"
                    ));
                }
            }
        }

        return response()->json([
            'code' => '200',
            'message' => 'Fund transferred successfully',
            'transaction' => $transaction
        ], 200);
    }

    public function createSavingFund (Request $request)
    {
        $user = $request->user();
        $authResponse = $this->authenticate($user, 'dependant');
        if ($authResponse) {
            return $authResponse;
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('savings')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->user()->id);
                }),
            ],
            'goal_amount' => 'nullable|numeric|min:0'
        ]);
    
        $goalAmount = $data['goal_amount'] ?? 0.00;
    
        try {
            $savingFund = Savings::create([
                'name' => $data['name'],
                'goal_amount' => $goalAmount,
                'user_id' => $request->user()->id,
            ]);
    
            return response()->json([
                'code' => 201,
                'message' => 'Savings fund created successfully',
                'savingFund' => $savingFund
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'An error occurred while creating the savings fund',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateSavingFund(Request $request, $id)
    {
        $user = $request->user();
        $authResponse = $this->authenticate($user, 'dependant');
        if ($authResponse) {
            return $authResponse;
        }

        $savingFund = Savings::find($id);

        if (!$savingFund) {
            return response()->json([
                'code' => '404',
                'message' => 'Saving fund not found'
            ], 404);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('savings')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->user()->id);
                })->ignore($id),
            ],
            'goal_amount' => 'nullable|numeric|min:0'
        ]);

        $goalAmount = $data['goal_amount'] ?? 0.00;

        try {
            if ($savingFund->amount != 0.00) {
                $remainingAmount = $goalAmount - $savingFund->amount;
            } else {
                $remainingAmount = 0.00;
            }

            $savingFund->update([
                'name' => $data['name'],
                'goal_amount' => $goalAmount,
                'remaining_amount' => $remainingAmount,
            ]);

            // Check and notify if saving goal is reached
            SpendingService::notifySavingGoalReached($user);

            return response()->json([
                'code' => 200,
                'message' => 'Savings fund updated successfully',
                'savingFund' => $savingFund
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'An error occurred while updating the savings fund',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function deleteSavingFund(Request $request, $id)
    {
        $user = $request->user();
        $authResponse = $this->authenticate($user, 'dependant');;
        if ($authResponse) {
            return $authResponse;
        }

        $savingFund = Savings::find($id);

        if (!$savingFund) {
            return response()->json([
                'code' => '404',
                'message' => 'Savings fund not found'
            ], 404);
        }

        try {
            // move money to wallet
            if ($savingFund->amount > 0) {
                $user->wallet->increment('balance', $savingFund->amount);
            }
            $savingFund->delete();

            return response()->json([
                'code' => 200,
                'message' => 'Savings fund deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'An error occurred while deleting the saving fund',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function transferToSavingFund(Request $request)
    {
        $user = $request->user();
        $authResponse = $this->authenticate($user, 'dependant');
        if ($authResponse) {
            return $authResponse;
        }

        $data = $request->all();

        if (!$data['savings_id'] || !$data['amount']) {
            return response()->json([
                'code' => '400',
                'message' => 'savings_id and amount are required'
            ], 400);
        }

        $savingFund = Savings::find($data['savings_id']);

        if (!$savingFund) {
            return response()->json([
                'code' => '404',
                'message' => 'Savings fund not found'
            ], 404);
        }

        // create transaction
        $transaction = $user->transactions()->create([
            'amount' => $request->amount,
            'transaction_type_id' => TransactionService::getTransactionTypeIdBySlug("add-to-savings"),
            'savings_id' => $savingFund->id,
            'status' => 'pending',
            'narration' => 'Savings',
            'pending_at' => now()
        ]);

        // update saving fund
        if ($savingFund->goal_amount != 0) {
            $newRemaining = $savingFund->remaining - $data['amount'];
            $newRemaining = max($newRemaining, 0); // ensure remaining amount doesn't go below zero
            $savingFund->remaining = $newRemaining;
        }

        $savingFund->amount += $data['amount'];
        $savingFund->save();

        //update user wallet
        $user->wallet->decrement('balance', $data['amount']);

        //update transaction
        $transaction->update([
            'status' => 'success',
            'completed_at' => now()
        ]);

        // Check and notify if saving goal is reached
        SpendingService::notifySavingGoalReached($user);

        return response()->json([
            'code' => '200',
            'message' => 'Fund transferred successfully',
            'transaction' => $transaction
        ], 200);
    }


    public function withdrawFromSavingFund(Request $request)
    {
        $user = $request->user();
        $authResponse = $this->authenticate($user, 'dependant');
        if ($authResponse) {
            return $authResponse;
        }

        $data = $request->all();

        if (!$data['savings_id'] || !$data['amount']) {
            return response()->json([
                'code' => '400',
                'message' => 'savings_id and amount are required'
            ], 400);
        }

        $savingFund = Savings::find($data['savings_id']);

        if (!$savingFund) {
            return response()->json([
                'code' => '404',
                'message' => 'Savings fund not found'
            ], 404);
        }

        if ($savingFund->amount < $data['amount']) {
            return response()->json([
                'code' => '400',
                'message' => 'Insufficient funds in the savings fund'
            ], 400);
        }

        // create transaction
        $transaction = $user->transactions()->create([
            'amount' => $data['amount'],
            'transaction_type_id' => TransactionService::getTransactionTypeIdBySlug("withdraw-from-savings"),
            'savings_id' => $savingFund->id,
            'status' => 'pending',
            'narration' => 'Withdrawal from Savings',
            'pending_at' => now()
        ]);

        // update saving fund
        $savingFund->amount -= $data['amount'];
        if ($savingFund->goal_amount != 0) {
            $newRemaining = $savingFund->remaining + $data['amount'];
            $savingFund->remaining = min($newRemaining, $savingFund->goal_amount); // ensure remaining amount doesn't exceed goal_amount
        }
        $savingFund->save();

        // update user wallet
        $user->wallet->increment('balance', $data['amount']);

        // update transaction
        $transaction->update([
            'status' => 'success',
            'completed_at' => now()
        ]);

        return response()->json([
            'code' => '200',
            'message' => 'Fund withdrawn successfully',
            'transaction' => $transaction
        ], 200);
    }

    public function getAllSavings(Request $request)
    {
        $user = $request->user();
        $authResponse = $this->authenticate($user, 'dependant');
        if ($authResponse) {
            return $authResponse;
        }

        // get all savings for the authenticated user
        $savings = $user->savings()->select('id', 'name', 'goal_amount', 'amount', 'remaining')->get();

        return response()->json([
            'code' => 200,
            'message' => 'Savings retrieved successfully',
            'savings' => $savings
        ], 200);
    }
}
