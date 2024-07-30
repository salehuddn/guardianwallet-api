<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserTransaction;
use Illuminate\Support\Facades\Log;
use App\Services\TransactionService;

class AnalyticService
{
    public function __construct()
    {
        // Initialize service
    }

    protected static $rules = [
        '50/30/20' => [
            'needs' => 0.50,
            'wants' => 0.30,
            'savings' => 0.20,
        ],
        '70/20/10' => [
            'needs' => 0.70,
            'wants' => 0.20,
            'savings' => 0.10,
        ],
    ];

    protected static $merchantTypeMappings = [
        'Food' => 'needs',
        'Transportation' => 'needs',
        'Utility' => 'needs',
        'Entertainment' => 'wants',
        'Health' => 'needs',
        'Education' => 'needs',
        'Retail' => 'wants',
        'Service' => 'needs',
        'Others' => 'wants',
    ];

    public static function monthlySummary($dependentId)
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

    public static function budgetAnalysis($dependentId, $currentMonth)
    {
        // fetch the dependent user
        $dependent = User::find($dependentId);

        // check if the dependent exists and has the role 'dependant'
        if ($dependent && $dependent->hasRole('dependant')) {
            // fetch total monthly income for the current month
            $income = $dependent->getTotalIncome($currentMonth);
        } else {
            return response()->json([
                'code' => 404,
                'message' => 'Dependent not found or not a dependant'
            ], 404);
        }

        if ($income == 0) {
            return response()->json([
                'code' => 404,
                'message' => 'No income found for the current month'
            ], 404);
        }

        // define the budget rule
        $selectedRule = '50/30/20';
        $budget = self::$rules[$selectedRule];

        // fetch transactions for the current month
        $transactions = UserTransaction::where('user_id', $dependentId)
                            ->where('created_at', 'like', $currentMonth . '%')
                            ->whereNotNull('merchant_id')
                            ->get();

        if ($transactions->isEmpty()) {
            return response()->json([
                'code' => 404,
                'message' => 'No transaction found for the current month'
            ], 404);
        }

        // categorize transactions
        $spending = [
            'needs' => 0,
            'wants' => 0,
            'savings' => 0,
        ];

        foreach ($transactions as $transaction) {
            $merchantType = $transaction->merchant->type->name;
            $mappedType = self::$merchantTypeMappings[$merchantType] ?? 'others';
    
            switch ($mappedType) {
                case 'needs':
                    $spending['needs'] += $transaction->amount;
                    break;
                case 'wants':
                    $spending['wants'] += $transaction->amount;
                    break;
                case 'savings':
                    $spending['savings'] += $transaction->amount;
                    break;
            }
        }

        // fetch savings for the current month
        $savingTransactionType = TransactionService::getTransactionTypeIdBySlug("add-to-savings");
        $savingsTransactions = UserTransaction::where('user_id', $dependentId)
                                ->where('created_at', 'like', $currentMonth . '%')
                                ->where('transaction_type_id', $savingTransactionType)
                                ->whereNotNull('savings_id')
                                ->get();
    
        foreach ($savingsTransactions as $savingTransaction) {
            $spending['savings'] += $savingTransaction->amount;
        }

        // calculate budget limits
        $limits = [
            'needs' => $income * $budget['needs'],
            'wants' => $income * $budget['wants'],
            'savings' => $income * $budget['savings'],
        ];

        // analyze spending
        $analysis = [
            'needs' => $spending['needs'] <= $limits['needs'] ? 'within budget' : 'over budget',
            'wants' => $spending['wants'] <= $limits['wants'] ? 'within budget' : 'over budget',
            'savings' => $spending['savings'] >= $limits['savings'] ? 'within target' : 'below target',
        ];

        // provide recommendations
        $recommendations = [];
        if ($spending['needs'] > $limits['needs']) {
            $recommendations[] = 'Reduce spending on essential expenses. Consider shopping around for better prices, using coupons, and avoiding impulse purchases.';
        } else {
            $recommendations[] = 'Great job keeping your essential expenses within budget! Keep up the good work and continue to prioritize necessary spending.';
        }

        if ($spending['wants'] > $limits['wants']) {
            $recommendations[] = 'Limit discretionary spending. Identify areas where you can cut back, such as dining out, entertainment, and non-essential shopping.';
        } else {
            $recommendations[] = 'Well done on managing your discretionary spending! Treat yourself occasionally, but continue to monitor and control your wants to stay within budget.';
        }

        if ($spending['savings'] < $limits['savings']) {
            $recommendations[] = 'Increase savings or investments. Look for ways to save more, such as setting up automatic transfers to your savings account and reducing unnecessary expenses.';
        } else {
            $recommendations[] = 'Excellent job meeting your savings target! Keep up the habit of saving regularly, and consider exploring investment opportunities to grow your savings.';
        }

        // percentages
        $percentages = [
            'needs' => ($spending['needs'] / $income) * 1,
            'wants' => ($spending['wants'] / $income) * 1,
            'savings' => ($spending['savings'] / $income) * 1,
        ];

        return [
            'month' => $currentMonth,
            'income' => $income,
            'spending' => $spending,
            'limits' => $limits,
            'analysis' => $analysis,
            'recommendations' => $recommendations,
            'percentages' => $percentages
        ];
    }
}
