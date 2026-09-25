<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payment\HitPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HitPayController extends Controller
{
    public function __construct(
        private HitPayService $hitPayService
    ) {}

    /**
     * Create HitPay Payment
     */
    public function create(Request $request)
    {
        return $this->hitPayService->createPayment(
            $request->user()
        );
    }

    /**
     * HitPay Webhook
     */
    public function webhook(Request $request)
    {
        return $this->hitPayService->handleWebhook(
            $request
        );
    }

    /**
     * Customer Return URL
     */
    public function callback(Request $request)
    {
        return $this->hitPayService->handleCallback(
            $request
        );
    }
}