<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\AddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        return AddressResource::collection($request->user()->addresses()->latest()->get());
    }

    public function store(AddressRequest $request)
    {
        $address = DB::transaction(function () use ($request) {
            $user = $request->user();
            $isDefault = $request->boolean('is_default') || ! $user->addresses()->exists();
            if ($isDefault) $user->addresses()->update(['is_default' => false]);
            return $user->addresses()->create([...$request->validated(), 'is_default' => $isDefault]);
        });

        return new AddressResource($address);
    }

    public function update(AddressRequest $request, Address $address)
    {
        abort_unless($address->user_id === $request->user()->id, 404);

        DB::transaction(function () use ($request, $address) {
            if ($request->boolean('is_default')) $request->user()->addresses()->update(['is_default' => false]);
            $address->update($request->validated());
        });

        return new AddressResource($address->fresh());
    }

    public function destroy(Request $request, Address $address)
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $address->delete();
        return response()->json(['success' => true]);
    }

    public function setDefault(Request $request, Address $address)
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        DB::transaction(function () use ($request, $address) {
            $request->user()->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return new AddressResource($address->fresh());
    }
}
