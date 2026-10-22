<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Shipping\JntService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncJntStatus extends Command
{
    protected $signature = 'jnt:sync';
    protected $description = 'Pull latest J&T status for open shipments';

    public function handle(JntService $jnt)
    {
        Order::whereNotNull('jnt_tracking_id')
            ->where(function ($q) {
                $q->whereNull('jnt_status')
                  ->orWhereNotIn('jnt_status', ['DELIVERED', 'RETURNED_TO_SENDER', 'CANCELLED']);
            })
            ->each(function ($order) use ($jnt) {
                try {
                    $status = $jnt->latestStatus($order->jnt_tracking_id);
                    if ($status && $status !== $order->jnt_status) {
                        $order->update([
                            'jnt_status' => $status,
                            'jnt_status_updated_at' => now(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('JNT sync failed for ' . $order->order_number . ': ' . $e->getMessage());
                }
            });
    }
}