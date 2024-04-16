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
        ];

        foreach ($transactionTypes as $transactionType) {
            TransactionType::create($transactionType);
        }
    }
}
