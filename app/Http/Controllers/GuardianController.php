<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use App\Models\User;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Models\UserTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterDependantRequest;

class GuardianController extends Controller
{
    public function guardianProfile(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('guardian')) {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized: You are not a guardian.'
            ], 401);
        }

        $guardian = User::with('dependants')->where('id', $user->id)->first();

        $guardianProfile = [
            'id' => $guardian->id,
            'name' => $guardian->name,
            'email' => $guardian->email,
            'dob' => $guardian->dob,
            'phone' => $guardian->phone,
            'role' => $guardian->roles->pluck('name')->first(),
            'dependants' => $guardian->dependants->map(function ($dependant) {
                return [
                    'id' => $dependant->id,
                    'name' => $dependant->name,
                    'email' => $dependant->email,
                    'dob' => $dependant->dob,
                    'phone' => $dependant->phone,
                ];
            }),
        ];

        return response()->json([
            'code' => 200,
            'user' => $guardianProfile,
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->only(['name', 'dob', 'phone', 'password']);
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

    public function dependants(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('guardian')) {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized: You are not a guardian.'
            ], 401);
        }

        $guardian = User::with('dependants')->where('id', $user->id)->first();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'dependant' => $guardian->dependants->map(function ($dependant) {
                return [
                    'id' => $dependant->id,
                    'name' => $dependant->name,
                    'email' => $dependant->email,
                    'dob' => $dependant->dob,
                    'phone' => $dependant->phone,
                    'wallet' => $dependant->wallet->balance
                ];
            }),
        ], 200);
    }

    public function registerDependant(RegisterDependantRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'dob' => $request->date_of_birth
        ]);

        $user->assignRole('dependant');
        $user->setSpendingLimit($request->spending_limit ?? 0.00);
        $user->save();

        $guardian = $request->user();
        $guardian->dependants()->attach($user->id);

        return response()->json([
            'code' => 201,
            'message' => 'User registered successfully'
        ], 201);
    }

    public function transactionHistory (Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('guardian')) {
            $transactions = $user->transactions()->with('transactionType')->latest()->get();

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'transactions' => $transactions
            ], 200);
        } else {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized: You are not a guardian.'
            ], 401);
        }
    }
    
    public function topupWallet(Request $request)
    {
        Stripe::setApiKey(config('stripe.sk'));
        $user = $request->user();
        // $user = User::find(2);

        if (!$request->filled('amount')) {
            return response()->json([
                'code' => 400,
                'message' => 'Amount is required'
            ], 400);
        }
        
        //create transaction
        $transaction = $user->transactions()->create([
            'amount' => $request->amount,
            'transaction_type_id' => 1, //topup wallet
            'status' => 'pending',
            'pending_at' => now()
        ]);

        if ($transaction) {
            $session = Session::create([
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'myr',
                            'product_data' => [
                                'name' => 'Topup Wallet',
                            ],
                            'unit_amount' => $transaction->amount * 100,
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => route('topup.success', ['transaction_id' => $transaction->id]),
                'cancel_url' => route('topup.cancel', ['transaction_id' => $transaction->id]),
            ]);
    
            // return redirect()->away($session->url);
            return response()->json([
                'code' => 200,
                'message' => 'Session created successfully',
                'checkoutUrl' => $session->url,
                'transactionId', $transaction->id
            ], 200);

        } else {
            return response()->json([
                'code' => 400,
                'message' => 'Amount is required'
            ], 400);
        }
    }

    public function success(Request $request)
    {
        $transaction_id = $request->query('transaction_id');
        Log::info('Transaction ID: ' . $transaction_id);

        //update transaction
        $transaction = UserTransaction::find($transaction_id);
        $transaction->status = 'success';
        $transaction->completed_at = now();
        $transaction->save();

        //update wallet
        $user = $transaction->user;
        $user->wallet->balance += $transaction->amount;
        $user->wallet->save();

        return response()->json([
            'code' => 200,
            'message' => 'Wallet topped up successfully',
            'transaction' => $transaction
        ], 200);
    }

    public function cancel(Request $request)
    {
        $transaction_id = $request->query('transaction_id');

        //update transaction
        $transaction = UserTransaction::find($transaction_id);
        $transaction->status = 'failed';
        $transaction->failed_at = now();
        $transaction->save();

        return response()->json([
            'code' => 200,
            'message' => 'Wallet topup cancelled',
            'transaction' => $transaction
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

    public function transferFund(Request $request)
    {
        $user = $request->user();

        if (!$request->filled('amount')) {
            return response()->json([
                'code' => 400,
                'message' => 'Amount is required'
            ], 400);
        }

        if (!$request->filled('dependant_id')) {
            return response()->json([
                'code' => 400,
                'message' => 'Dependant ID is required'
            ], 400);
        }

        $dependant = User::find($request->dependant_id);

        if (!$dependant) {
            return response()->json([
                'code' => 404,
                'message' => 'Dependant not found'
            ], 404);
        }

        if (!$user->dependants->contains($dependant->id)) {
            return response()->json([
                'code' => 400,
                'message' => 'Dependant not found'
            ], 400);
        }

        if ($user->wallet->balance < $request->amount) {
            return response()->json([
                'code' => 400,
                'message' => 'Insufficient balance'
            ], 400);
        }

        //create transaction
        $transaction = $user->transactions()->create([
            'amount' => $request->amount,
            'transaction_type_id' => 2, //transfer fund
            'status' => 'pending',
            'pending_at' => now()
        ]);

        if ($transaction) {
            //update wallet
            $user->wallet->balance -= $transaction->amount;
            $user->wallet->save();

            //update dependant wallet
            $dependant->wallet->balance += $transaction->amount;
            $dependant->wallet->save();

            //update transaction
            $transaction->status = 'success';
            $transaction->completed_at = now();
            $transaction->save();

            return response()->json([
                'code' => 200,
                'message' => 'Fund transferred successfully',
                'transaction' => $transaction
            ], 200);
        } else {
            return response()->json([
                'code' => 400,
                'message' => 'Amount is required'
            ], 400);
        }
    }

    public function updateDependent(Request $request)
    {
        $user = $request->user();

        if (!$request->filled('dependant_id')) {
            return response()->json([
                'code' => 400,
                'message' => 'Dependant ID is required'
            ], 400);
        }

        $dependant = User::find($request->dependant_id);

        if (!$dependant) {
            return response()->json([
                'code' => 404,
                'message' => 'Dependant not found'
            ], 404);
        }

        if (!$user->dependants->contains($dependant->id)) {
            return response()->json([
                'code' => 400,
                'message' => 'Dependant not found'
            ], 400);
        }

        $data = $request->only(['name', 'dob', 'phone', 'password', 'spending_limit']);
        $data = array_filter($data);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $dependant->update($data);

        return response()->json([
            'code' => 200,
            'message' => 'Dependant updated successfully'
        ], 200);
    }
}
