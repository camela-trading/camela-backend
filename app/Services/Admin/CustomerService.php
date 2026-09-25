<?php

namespace App\Services\Admin;

use App\Models\User;

class CustomerService
{
    public function index()
    {
        return User::with([
                'orders',
                'addresses',
            ])
            ->where('role_id', 2)
            ->latest()
            ->get()
            ->map(function ($user) {

                $orders = $user->orders;

                $defaultAddress = $user->addresses
                    ->where('is_default', true)
                    ->first();

                $position = User::where('role_id', 2)
                    ->where('id', '<=', $user->id)
                    ->count();

                $displayId = 'C' . str_pad(
                    $position,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

                return [

                    'id' => (string) $user->id,

                    'display_id' => $displayId,

                    'name' => $user->name,

                    'email' => $user->email,

                    'phone' => $user->phone,

                    'location' => $defaultAddress?->city ?? '-',

                    'address' => $defaultAddress
                        ? collect([
                            $defaultAddress->address,
                            $defaultAddress->city,
                            $defaultAddress->state,
                            $defaultAddress->zip_code,
                            $defaultAddress->country,
                        ])->filter()->implode(', ')
                        : '-',

                    'orders' => $orders->count(),

                    'spent' => (float) $orders->sum('grand_total'),

                    'joined' => $user->created_at,

                    'status' => 'active',

                ];

            });
    }
}
