<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\UserTransaction;
use App\Services\AnalyticService;

class AnalyticController extends Controller
{
    // public function index ()
    // {
    //     $analyze = self::analyze($dependantId;
    // }

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
}
