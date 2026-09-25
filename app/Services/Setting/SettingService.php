<?php

namespace App\Services\Setting;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SettingService
{
    public function get(User $user)
    {
        return Setting::firstOrCreate(

            [

                'user_id' => $user->id,

            ],

            [

                'dark_mode' => false,

                'language' => 'en',

                'order_updates' => true,

                'promotions' => true,

                'profile_visible' => true,

            ]

        );
    }

    public function update(User $user, array $data)
    {
        $setting = $this->get($user);

        $setting->update($data);

        return $setting->fresh();
    }

    public function changePassword(User $user, string $password): void
    {
        $user->update([
            'password' => Hash::make($password),
        ]);
    }
}
