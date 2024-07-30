<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use App\Services\TransactionService;
use App\Notifications\SpendingLimitNotification;

class SpendingService
{
    public static function checkLimit(User $user, $amount)
    {
        // get user spending limit
        $spendingLimit = $user->getSpendingLimit();

        // get current week's start and end dates
        $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d H:i:s');
        $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d H:i:s');

        // calculate the total amount spent by user within the current week for spending transactions only
        $totalSpentThisWeek = $user->transactions()
            ->where('created_at', '>=', $startOfWeek)
            ->where('created_at', '<=', $endOfWeek)
            ->where('status', 'success')
            ->where('transaction_type_id', TransactionService::getTransactionTypeIdBySlug("transfer-fund")) 
            ->sum('amount');

        // Log the spending details
        \Log::info('Spending Limit Check:', [
            'spendingLimit' => $spendingLimit,
            'totalSpentThisWeek' => $totalSpentThisWeek,
            'amount' => $amount
        ]);

        // check if the total spent plus the current transaction amount exceeds the spending limit
        if ($totalSpentThisWeek + $amount > $spendingLimit) {
            return [
            'code' => '400',
            'message' => 'Weekly spending limit exceeded'
            ];
        }

        return null;
    }


    public static function hasAlmostExceededLimit(User $user)
    {
        // get user spending limit
        $spendingLimit = $user->getSpendingLimit();
        
        // return null if the spending limit is 0.00
        if ($spendingLimit == 0.00) {
            return null;
        }

        $threshold = 0.7 * $spendingLimit; // 70% of the spending limit

        // get current week's start and end dates
        $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d H:i:s');
        $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d H:i:s');

        // calculate the total amount spent by user within the current week
        $totalSpentThisWeek = $user->transactions()
            ->where('transaction_type_id', TransactionService::getTransactionTypeIdBySlug("transfer-fund"))
            ->whereNotNull('merchant_id')
            ->where('created_at', '>=', $startOfWeek)
            ->where('created_at', '<=', $endOfWeek)
            ->where('status', 'success')
            ->sum('amount');

        // check if the total spent exceeds 70% of the spending limit
        if ($totalSpentThisWeek >= $threshold) {
            return [
                'code' => 200,
                'message' => 'You have spent more than 70% of your spending limit'
            ];
        }

        return null;
    }

    public static function notifySavingGoalReached(User $user)
    {
        // Get all saving funds for the user
        $savings = $user->savings;

        foreach ($savings as $saving) {
            if ($saving->goal_amount != 0 && $saving->amount >= $saving->goal_amount) {
                // Notify the user
                $user->notify(new SpendingLimitNotification(
                    'Saving Goal Reached',
                    'Congratulations! Your saving fund "' . $saving->name . '" has reached its goal of ' . $saving->goal_amount . '.'
                ));
            }
        }
    }

}
