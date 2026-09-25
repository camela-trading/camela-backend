<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Services\Currency\CurrencyService;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    public function __invoke(CurrencyService $currencyService): JsonResponse
    {
        return response()->json($currencyService->rates());
    }
}
