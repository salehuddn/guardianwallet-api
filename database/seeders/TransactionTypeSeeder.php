<?php

namespace Database\Seeders;

use App\Models\TransactionType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TransactionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transactionTypes = [
            [
                'name' => 'Top-up Wallet',
                'slug' => 'topup-wallet',
            ],
            [
                'name' => 'Transfer Fund',
                'slug' => 'transfer-fund',
            ],
            [
                'name' => 'Make Payment',
                'slug' => 'make-payment',
            ],
            [
                'name' => 'Receive Fund',
                'slug' => 'receive-fund',
            ],
            [
                'name' => 'Add to Savings',
                'slug' => 'add-to-savings',
            ],
            [
                'name' => 'Withdraw from Savings',
                'slug' => 'withdraw-from-savings',
            ],
        ];

        foreach ($transactionTypes as $transactionType) {
            TransactionType::create($transactionType);
        }
    }
}
