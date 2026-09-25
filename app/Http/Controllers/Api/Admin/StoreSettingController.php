<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\StoreSettingService;
use Illuminate\Http\Request;

class StoreSettingController extends Controller
{
    public function __construct(
        private StoreSettingService $storeSettingService
    ) {}

    /**
     * Get Store Settings
     */
    public function show()
    {
        return response()->json([

            'success' => true,

            'data' => $this->storeSettingService->get()

        ]);
    }

    /**
     * Get public store status for the customer-facing app.
     */
    public function publicStatus()
    {
        $settings = $this->storeSettingService->get();

        return response()->json([
            'success' => true,
            'data' => [
                'store_name' => $settings->store_name,
                'maintenance_mode' => $settings->maintenance_mode,
                'standard_shipping' => $settings->standard_shipping,
                'express_shipping' => $settings->express_shipping,
                'overnight_shipping' => $settings->overnight_shipping,
                'free_shipping_threshold' => $settings->free_shipping_threshold,
                'tax_rate' => $settings->tax_rate,
            ],
        ]);
    }

    /**
     * Update Store Settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Store Information
            |--------------------------------------------------------------------------
            */

            'store_name' => 'sometimes|string|max:255',

            'tagline' => 'nullable|string|max:255',

            'support_email' => 'nullable|email|max:255',

            'phone' => 'nullable|string|max:50',

            'address' => 'nullable|string',

            'logo' => 'nullable|string',

            /*
            |--------------------------------------------------------------------------
            | Shipping
            |--------------------------------------------------------------------------
            */

            'standard_shipping' => 'sometimes|numeric|min:0',

            'express_shipping' => 'sometimes|numeric|min:0',

            'overnight_shipping' => 'sometimes|numeric|min:0',

            'free_shipping_threshold' => 'sometimes|numeric|min:0',

            /*
            |--------------------------------------------------------------------------
            | Tax
            |--------------------------------------------------------------------------
            */

            'tax_rate' => 'sometimes|numeric|min:0|max:100',

            /*
            |--------------------------------------------------------------------------
            | Inventory
            |--------------------------------------------------------------------------
            */

            'default_low_stock_threshold' => 'sometimes|integer|min:0',

            /*
            |--------------------------------------------------------------------------
            | Maintenance
            |--------------------------------------------------------------------------
            */

            'maintenance_mode' => 'sometimes|boolean',

            /*
            |--------------------------------------------------------------------------
            | Notifications
            |--------------------------------------------------------------------------
            */

            'notify_new_order' => 'sometimes|boolean',

            'notify_low_stock' => 'sometimes|boolean',

            'notify_new_customer' => 'sometimes|boolean',

            'notify_order_delivered' => 'sometimes|boolean',

        ]);

        $settings = $this->storeSettingService->update($validated);

        return response()->json([

            'success' => true,

            'message' => 'Store settings updated successfully.',

            'data' => $settings

        ]);
    }
}
