<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\UpdatePasswordRequest;
use App\Services\Setting\SettingService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(

        private SettingService $settingService

    ) {}

    public function show(Request $request)
    {
        return response()->json(

            $this->settingService->get(

                $request->user()

            )

        );
    }

    public function update(

        Request $request

    )
    {
        return response()->json(

            $this->settingService->update(

                $request->user(),

                $request->validate([

                    'dark_mode' => 'boolean',

                    'language' => 'string|max:10',

                    'order_updates' => 'boolean',

                    'promotions' => 'boolean',

                    'profile_visible' => 'boolean',

                ])

            )

        );
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $this->settingService->changePassword(
            $request->user(),
            $request->validated()['password']
        );

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }
}
