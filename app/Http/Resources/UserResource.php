<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    private function resolveImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $normalized = ltrim($path, '/');

        if (str_starts_with($normalized, 'storage/')) {
            return asset($normalized);
        }

        return Storage::disk('public')->url($normalized);
    }

    public function toArray($request): array
    {

        $displayId = null;

            if ($this->role?->name === 'ADMIN') {

                $position = User::whereHas('role', function ($query) {

                    $query->where('name', 'ADMIN');

                })
                ->where('id', '<=', $this->id)
                ->count();

                $displayId = 'A' . str_pad(

                    $position,

                    3,

                    '0',

                    STR_PAD_LEFT

                );

            } else {

                $position = User::whereHas('role', function ($query) {

                    $query->where('name', 'CUSTOMER');

                })
                ->where('id', '<=', $this->id)
                ->count();

                $displayId = 'C' . str_pad(

                    $position,

                    4,

                    '0',

                    STR_PAD_LEFT

                );

            }

        return [

            'id' => $this->id,

            'display_id' => $this->display_id,

            'username' => $this->username,

            'email' => $this->email,

            'phone' => $this->phone,

            'email_verified_at' => $this->email_verified_at?->toISOString(),

            'avatar' => $this->resolveImageUrl($this->avatar),

            'role_id' => $this->role_id,

            'is_admin' => $this->role?->name === 'ADMIN',

            'name' => $this->name,

            'created_at' => $this->created_at
                ? $this->created_at
                    ->timezone('Asia/Singapore')
                    ->format('Y-m-d H:i:s')
                : null,

            'updated_at' => $this->updated_at
                ? $this->updated_at
                    ->timezone('Asia/Singapore')
                    ->format('Y-m-d H:i:s')
                : null,

        ];
    }
}
