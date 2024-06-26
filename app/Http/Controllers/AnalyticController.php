<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\UserTransaction;
use App\Services\AnalyticService;
use App\Services\TransactionService;

use function PHPUnit\Framework\isEmpty;

class AnalyticController extends Controller
{
    protected $rules = [
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

    protected $merchantTypeMappings = [
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

    public function analyze(Request $request)
    {
        $rules = [
            'high_spending' => 100,
            'low_balance' => 50,
            'frequent_small_transactions' => 10,
            'small_transaction_amount' => 5,
        ];

        $dependents = User::role('dependant')->get();
        $analytics = [];

        foreach ($dependents as $dependent) {
            $monthlySummary = AnalyticService::monthlySummary($dependent->id);
            $categoryBreakdown = AnalyticService::categoryBreakdown($dependent->id);
            $spendingPatterns = AnalyticService::spendingPatterns($dependent->id);
            $spendingTips = AnalyticService::spendingTips($dependent->id);
            $merchantAnalysis = AnalyticService::merchantAnalysis($dependent->id);

            $analytics[$dependent->id] = [
                'name' => $dependent->name,
                'monthly_summary' => $monthlySummary,
                'category_breakdown' => $categoryBreakdown,
                'spending_patterns' => $spendingPatterns,
                'recommendations' => $spendingTips, 
                'merchant_analysis' => $merchantAnalysis
            ];

            // // Calculate the start and end dates for the previous month
            // $startDate = Carbon::now()->startOfMonth();
            // $endDate = Carbon::now()->endOfMonth();

            // // Get transactions for the previous month
            // $transactions = UserTransaction::where('user_id', $dependent->id)
            //     ->whereBetween('created_at', [$startDate, $endDate])
            //     ->get();

            // // Skip new users with no transactions in the previous month
            // if ($transactions->isEmpty()) {
            //     continue;
            // }

            // $total_spent = $transactions->sum('amount');
            // $small_transactions = $transactions->filter(function ($transaction) use ($rules) {
            //     return $transaction->amount < $rules['small_transaction_amount'];
            // })->count();

            // // Apply rule-based analysis
            // $analysis = [];
            // if ($total_spent > $rules['high_spending']) {
            //     $analysis['high_spending'] = true;
            // }

            // if ($dependent->balance < $rules['low_balance']) {
            //     $analysis['low_balance'] = true;
            // }

            // if ($small_transactions > $rules['frequent_small_transactions']) {
            //     $analysis['frequent_small_transactions'] = true;
            // }

            // $analytics[$dependent->id] = [
            //     'dependent' => $dependent,
            //     'total_spent' => $total_spent,
            //     'small_transactions' => $small_transactions,
            //     'analysis' => $analysis,
            //     'month' => $startDate->format('F Y'),
            // ];
        }

        return response()->json($analytics);
    }

    public function budgetAnalysis($dependentId, Request $request)
    {
        $user = $request->user();
        $authResponse = $this->authenticate($user, ['guardian', 'dependant']);
        if ($authResponse) {
            return $authResponse;
        }

        $currentMonth = $request->input('current_month') ?? now()->format('Y-m');

        $result = AnalyticService::budgetAnalysis($dependentId, $currentMonth);

        if (is_array($result)) {
            return response()->json([
                'code' => 200,
                'data' => $result
            ]);
        }
    
        return $result;
    }
}
