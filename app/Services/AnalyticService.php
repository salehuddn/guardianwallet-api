<?php

namespace App\Services;

use App\Models\UserTransaction;
use Illuminate\Support\Facades\Log;

class AnalyticService
{
    public function __construct()
    {
        // Initialize service
    }

    public static function monthlySummary ($dependentId) 
    {
        $currentMonth = now()->format('Y-m');
        $totalSpending = UserTransaction::where('user_id', $dependentId)
                            ->where('created_at', 'like', $currentMonth . '%')
                            ->sum('amount');

        $previousMonth = now()->subMonth()->format('Y-m');
        $previousMonthSpending = UserTransaction::where('user_id', $dependentId)
                                    ->where('created_at', 'like', $previousMonth . '%')
                                    ->sum('amount');

        return [
            'current_month' => $currentMonth,
            'total_spending' => $totalSpending,
            'previous_month' => $previousMonth,
            'previous_month_spending' => $previousMonthSpending,
        ];
    }

    public static function categoryBreakdown($dependentId)
    {
        $currentMonth = now()->format('Y-m');
        $spendingByCategory = UserTransaction::selectRaw('transaction_types.name as type_name, SUM(user_transactions.amount) as total')
            ->join('transaction_types', 'user_transactions.transaction_type_id', '=', 'transaction_types.id')
            ->where('user_transactions.user_id', $dependentId)
            ->where('user_transactions.created_at', 'like', $currentMonth . '%')
            ->groupBy('transaction_types.name')
            ->get();

        return $spendingByCategory;
    }

    public static function spendingPatterns($dependentId)
    {
        $transactions = UserTransaction::where('user_id', $dependentId)
                            ->orderBy('created_at', 'asc')
                            ->get();

        $patterns = [];
        foreach ($transactions as $transaction) {
            $date = $transaction->created_at->format('Y-m-d');
            if (!isset($patterns[$date])) {
                $patterns[$date] = 0;
            }
            $patterns[$date] += $transaction->amount;
        }

        return $patterns;
    }

    public static function spendingTips($dependentId)
    {
        $spendingByCategory = self::getCategoryBreakdown($dependentId);
        $recommendations = [];

        foreach ($spendingByCategory as $merchantType => $total) {
            if ($total > 100) { // Example threshold
                $recommendations[] = "Consider reducing spending in " . $merchantType;
            }
        }

        return $recommendations;
    }

    private static function getCategoryBreakdown($dependentId)
    {
        $currentMonth = now()->format('Y-m');
        $transactions = UserTransaction::where('user_id', $dependentId)
            ->where('created_at', 'like', $currentMonth . '%')
            ->with('merchant.type')
            ->get();

        $breakdown = $transactions->groupBy(function ($transaction) {
            return $transaction->merchant->type->name;
        })->map(function ($group) {
            return $group->sum('amount');
        });

        return $breakdown;
    }

    public static function merchantAnalysis($dependentId)
    {
        $currentMonth = now()->format('Y-m');
        $transactions = UserTransaction::with('merchant')
        ->where('user_id', $dependentId)
        ->where('created_at', 'like', $currentMonth . '%')
        ->get();

        $spendingByMerchant = $transactions->filter(function ($transaction) {
            return $transaction->merchant;
        })->groupBy(function ($transaction) {
            return $transaction->merchant->name;
        })->map(function ($group) {
            return $group->sum('amount');
        });

        return $spendingByMerchant;
    }
}