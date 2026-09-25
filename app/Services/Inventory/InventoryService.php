<?php

namespace App\Services\Inventory;

use App\Models\Product;
use App\Models\InventoryTransaction;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\Notifications\AdminNotificationService;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(
        private AdminNotificationService $notificationService
    ) {}

    public function adjustStock(
        Product $product,
        string $type,
        int $quantity,
        ?User $user = null,
        ?string $remarks = null
    ): InventoryTransaction
    {
        return DB::transaction(function () use (
            $product,
            $type,
            $quantity,
            $user,
            $remarks
        ) {

            $before = $product->stock;

            switch ($type) {

                case 'STOCK_IN':

                    $after = $before + $quantity;

                    break;

                case 'STOCK_OUT':

                case 'SALE':

                    $after = max(0, $before - $quantity);

                    break;

                case 'RETURN':

                    $after = $before + $quantity;

                    break;

                case 'ADJUSTMENT':

                    $after = $quantity;

                    break;

                default:

                    throw new \Exception('Invalid inventory transaction type.');
            }

            $product->update([

                'stock' => $after

            ]);

            $settings = StoreSetting::first();
            $threshold = $settings?->default_low_stock_threshold ?? 0;

            if (
                $this->notificationService->isEnabled('notify_low_stock') &&
                in_array($type, ['STOCK_OUT', 'SALE'], true) &&
                $after <= $threshold
            ) {
                $this->notificationService->notify(
                    'notify_low_stock',
                    'low_stock',
                    'Low Stock Warning',
                    "{$product->title} is now running low with {$after} item(s) left.",
                    [
                        'product_id' => $product->id,
                        'product_title' => $product->title,
                        'stock_after' => $after,
                        'threshold' => $threshold,
                    ],
                    "/admin/products/{$product->id}"
                );
            }

            return InventoryTransaction::create([

                'product_id' => $product->id,

                'user_id' => $user?->id,

                'type' => $type,

                'quantity' => $quantity,

                'stock_before' => $before,

                'stock_after' => $after,

                'remarks' => $remarks,

            ]);
        });
    }
}
