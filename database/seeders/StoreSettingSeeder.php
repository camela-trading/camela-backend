<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        StoreSetting::firstOrCreate(
            ['id' => 1],
            [
                'store_name' => 'Camela Group',
                'tagline' => 'Science-Backed Wellness for Every Family',
            'support_email' => 'camela.trading@gmail.com',
                'phone' => '+65-80641997',
                'address' => 'Singapore',
                'logo' => null,
                'standard_shipping' => 5.99,
                'express_shipping' => 12.99,
                'overnight_shipping' => 24.99,
                'free_shipping_threshold' => 75,
                'tax_rate' => 10,
                'default_low_stock_threshold' => 5,
                'maintenance_mode' => false,
                'notify_new_order' => true,
                'notify_low_stock' => true,
                'notify_new_customer' => true,
            ]
        );
    }
}
