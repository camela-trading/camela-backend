<?php

namespace App\Services\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function dashboard(): array
    {
        return [

            'stats' => $this->stats(),

            'revenue' => $this->revenue(),

            'weekly_revenue' => $this->weeklyRevenue(),

            'categories' => $this->categories(),

            'recent_orders' => $this->recentOrders(),

            'low_stock' => $this->lowStock(),

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Cards
    |--------------------------------------------------------------------------
    */

    private function stats(): array
    {
        $revenue = Order::sum('grand_total');

        $orders = Order::count();

        $customers = User::where('role_id',2)->count();

        return [

            'totalRevenue'=>[

                'label'=>'Revenue',

                'value'=>(float)$revenue,

                'change'=>0

            ],

            'totalOrders'=>[

                'label'=>'Orders',

                'value'=>$orders,

                'change'=>0

            ],

            'totalCustomers'=>[

                'label'=>'Customers',

                'value'=>$customers,

                'change'=>0

            ],

            'avgOrderValue'=>[

                'label'=>'Average',

                'value'=>$orders
                    ? round($revenue/$orders,2)
                    :0,

                'change'=>0

            ]

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Revenue Chart
    |--------------------------------------------------------------------------
    */

    private function revenue()
    {
        $customers = User::where('role_id', 2)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as customers')
            ->groupByRaw('MONTH(created_at)')
            ->get()
            ->keyBy('month');

        return Order::selectRaw('
                MONTH(created_at) as month,
                SUM(grand_total) as revenue,
                COUNT(*) as orders
            ')
            ->groupByRaw('MONTH(created_at)')
            ->orderByRaw('MONTH(created_at)')
            ->get()
            ->map(function ($row) use ($customers) {

                $customerCount = $customers->get($row->month);

                return [

                    'month' => date('M', mktime(0,0,0,$row->month,1)),

                    'revenue' => (float) $row->revenue,

                    'orders' => (int) $row->orders,

                    'customers' => $customerCount
                        ? (int) $customerCount->customers
                        : 0,

                ];
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Category Chart
    |--------------------------------------------------------------------------
    */

    private function categories()
    {
        return \App\Models\OrderItem::query()
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('
                categories.name,
                SUM(order_items.quantity) as value
            ')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('value')
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->name,
                    'value' => (int) $row->value,
                ];
            });
    }

    private function weeklyRevenue()
    {
        $customers = User::where('role_id', 2)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as customers')
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('date');

        return Order::selectRaw('
                DATE(created_at) as date,
                SUM(grand_total) as revenue,
                COUNT(*) as orders
            ')
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get()
            ->map(function ($row) use ($customers) {

                $customerCount = $customers->get($row->date);

                return [

                    'month' => $row->date,

                    'revenue' => (float) $row->revenue,

                    'orders' => (int) $row->orders,

                    'customers' => $customerCount
                        ? (int) $customerCount->customers
                        : 0,

                ];
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Recent Orders
    |--------------------------------------------------------------------------
    */

    private function recentOrders()
    {
        return Order::with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($order) {

                return [

                    'id'=>$order->order_number,

                    'customer'=>$order->user?->name,

                    'email'=>$order->user?->email,

                    'items'=>$order->items()->count(),

                    'total'=>(float)$order->grand_total,

                    'status'=>$order->order_status,

                ];

            });
    }

    /*
    |--------------------------------------------------------------------------
    | Low Stock
    |--------------------------------------------------------------------------
    */

    private function lowStock()
    {
        return Product::with('category')
            ->whereColumn(

                'stock',

                '<=',

                'low_stock_alert'

            )
            ->get()
            ->map(function ($product) {

                return [

                    'id'=>$product->id,

                    'name'=>$product->title,

                    'category'=>$product->category?->name,

                    'stock'=>$product->stock,

                ];

            });
    }
}
