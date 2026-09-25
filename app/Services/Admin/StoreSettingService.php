<?php

namespace App\Services\Admin;

use App\Models\StoreSetting;

class StoreSettingService
{
    /**
     * Get store settings.
     */
    public function get(): StoreSetting
    {
        return StoreSetting::firstOrCreate(
            ['id' => 1],
            [
                'store_name' => 'Camela Group',
                'tagline' => 'Science-Backed Wellness for Every Family',
                'support_email' => 'camela.trading@gmail.com',
                'phone' => '+65-80641997',
                'address' => 'Singapore',

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
                'notify_order_delivered' => true,
            ]
        );
    }

    /**
     * Update store settings.
     */
    public function update(array $data): StoreSetting
    {
        $settings = $this->get();

        $settings->update($data);

        return $settings->fresh();
    }
}
