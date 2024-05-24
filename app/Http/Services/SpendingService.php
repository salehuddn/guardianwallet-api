<?php

namespace App\Http\Services;

use App\Models\User;
use Carbon\Carbon;

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
      ->where('transaction_type_id', 2) // Assuming transaction_type_id 2 is for spending
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
      
      // Return null if the spending limit is 0.00
      if ($spendingLimit == 0.00) {
          return null;
      }

      $threshold = 0.7 * $spendingLimit; // 70% of the spending limit

      // get current week's start and end dates
      $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d H:i:s');
      $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d H:i:s');

      // calculate the total amount spent by user within the current week
      $totalSpentThisWeek = $user->transactions()
          ->where('created_at', '>=', $startOfWeek)
          ->where('created_at', '<=', $endOfWeek)
          ->where('status', 'success')
          ->sum('amount');

      // check if the total spent exceeds 70% of the spending limit
      if ($totalSpentThisWeek >= $threshold) {
          return [
              'code' => '200',
              'message' => 'You have spent more than 70% of your spending limit'
          ];
      }

      return null;
  }

}
