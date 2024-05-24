<?php

namespace App\Services;

use App\Models\TransactionType;

class TransactionService
{
  public function __construct()
  {
    // Constructor logic goes here
  }

  public static function getTransactionTypeIdBySlug($transactionType): int
  {
    $transactionType = TransactionType::where('slug', $transactionType)->first();
    return $transactionType->id;
  }
}