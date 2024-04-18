<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\MerchantType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $merchants = [
            [
                'name' => 'Tasty Burgers',
                'registration_no' => 'TB123',
                'merchant_type_id' => MerchantType::where('name', 'Food')->first()->id,
            ],
            [
                'name' => 'City Cab',
                'registration_no' => 'CC456',
                'merchant_type_id' => MerchantType::where('name', 'Transportation')->first()->id,
            ],
            [
                'name' => 'PowerCo',
                'registration_no' => 'PC789',
                'merchant_type_id' => MerchantType::where('name', 'Utility')->first()->id,
            ],
            [
                'name' => 'CineMagic',
                'registration_no' => 'CM101',
                'merchant_type_id' => MerchantType::where('name', 'Entertainment')->first()->id,
            ],
            [
                'name' => 'Healthy Living Clinic',
                'registration_no' => 'HLC202',
                'merchant_type_id' => MerchantType::where('name', 'Health')->first()->id,
            ],
            [
                'name' => 'Academic Excellence Institute',
                'registration_no' => 'AEI303',
                'merchant_type_id' => MerchantType::where('name', 'Education')->first()->id,
            ],
            [
                'name' => 'Fashion Junction',
                'registration_no' => 'FJ404',
                'merchant_type_id' => MerchantType::where('name', 'Retail')->first()->id,
            ],
            [
                'name' => 'QuickFix Services',
                'registration_no' => 'QFS505',
                'merchant_type_id' => MerchantType::where('name', 'Service')->first()->id,
            ],
        ];

        foreach ($merchants as $merchant) {
            Merchant::create($merchant);
        }
    }
}
