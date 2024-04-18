<?php

namespace Database\Seeders;

use App\Models\MerchantType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MerchantTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Food'],
            ['name' => 'Transportation'],
            ['name' => 'Utility'],
            ['name' => 'Entertainment'],
            ['name' => 'Health'],
            ['name' => 'Education'],
            ['name' => 'Retail'],
            ['name' => 'Service'],
            ['name' => 'Others'],
        ];

        foreach ($types as $type) {
            MerchantType::create($type);
        }
    }
}
