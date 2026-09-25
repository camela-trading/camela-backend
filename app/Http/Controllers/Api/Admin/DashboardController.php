<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;

class DashboardController extends Controller
{
    public function __construct(

        private DashboardService $dashboardService

    ) {}

    public function index()
    {
        return response()->json(

            $this->dashboardService->dashboard()

        );
    }
}