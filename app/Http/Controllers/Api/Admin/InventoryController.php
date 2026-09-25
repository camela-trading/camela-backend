<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockAdjustmentRequest;
use App\Http\Resources\InventoryTransactionResource;
use App\Models\Product;
use App\Services\Inventory\InventoryService;

class InventoryController extends Controller
{
    public function __construct(

        private InventoryService $inventoryService

    ) {}

    public function store(

        StockAdjustmentRequest $request,

        Product $product

    )
    {

        $transaction =

            $this->inventoryService->adjustStock(

                $product,

                $request->type,

                $request->quantity,

                $request->user(),

                $request->remarks

            );

        return new InventoryTransactionResource(

            $transaction->load([

                'product',

                'user'

            ])

        );

    }

    public function history(Product $product)
    {

        return InventoryTransactionResource::collection(

            $product
                ->inventoryTransactions()
                ->latest()
                ->with([
                    'user'
                ])
                ->paginate(20)

        );

    }
}