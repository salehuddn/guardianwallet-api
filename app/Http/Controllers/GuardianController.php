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
        // $user = $request->user()->load('dependents');

        // return response()->json([
        //     'code' => 200,
        //     'message' => 'success',
        //     'data' => $user
        // ], 200);

        $guardian = $request->user();

        if ($guardian->hasRole('guardian')) {
            if ($guardian->dependants->isNotEmpty()) {
                return response()->json([
                    'code' => 200,
                    'user' => $guardian,
                ], 200);
            } else {
                return response()->json([
                    'code' => 200,
                    'user' => $guardian,
                ], 200);
            }
        } else {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized: You are not a guardian.'
            ], 401);
        }
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

    public function guardianDependents(Request $request)
    {
        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $request->user()->dependents
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
            $transactions = $user->transactions()->with('transactionType')->get();

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
        // $user = User::find(4);

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
    
            return redirect()->away($session->url);

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
            'message' => 'Wallet topped up successfully'
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
            'message' => 'Wallet topup cancelled'
        ], 200);
    }

    public function wallet(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'wallet' => $user->wallet
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
                'message' => 'Fund transferred successfully'
            ], 200);
        } else {
            return response()->json([
                'code' => 400,
                'message' => 'Amount is required'
            ], 400);
        }
    }
}
