<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return new UserResource(

            $request->user()

        );
    }

    public function update(
        UpdateProfileRequest $request
    )
    {
        $user = $request->user();
        $emailChanged = $user->email !== $request->email;

        $user->update([

            'name' => trim(

                $request->firstname.' '.$request->lastname

            ),

            'username' => $request->username,

            'email' => $request->email,

            'phone' => $request->phone,

        ]);

        if ($emailChanged) {
            $user->forceFill([
                'email_verified_at' => null,
            ])->save();
        }

        $user->refresh();

        return response()->json([

            'message' => 'Profile updated successfully.',

            'user' => new UserResource($user)

        ]);
    }

    public function updateAvatar(Request $request)
    {
        $data = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();
        $oldAvatar = $user->avatar;
        $file = $data['avatar'];
        $extension = $file->getClientOriginalExtension();
        $filename = sprintf('user_%d_%s_%04d.%s', $user->id, now()->timestamp, random_int(0, 9999), $extension);
        $path = $file->storeAs('avatars', $filename, 'public');

        $user->update([
            'avatar' => $path,
        ]);

        if ($oldAvatar && $oldAvatar !== $path) {
            Storage::disk('public')->delete($oldAvatar);
        }

        $user->refresh();

        return response()->json([
            'message' => 'Profile photo updated successfully.',
            'user' => new UserResource($user),
        ]);
    }
}
