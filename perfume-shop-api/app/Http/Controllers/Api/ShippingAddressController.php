<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShippingAddressRequest;
use App\Http\Requests\UpdateShippingAddressRequest;
use App\Http\Resources\ShippingAddressResource;
use App\Models\ShippingAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingAddressController extends Controller
{
    /**
     * Display a listing of user's shipping addresses.
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = ShippingAddress::where('user_id', $request->user()->id)->get();

        return response()->json([
            'success' => true,
            'data' => ShippingAddressResource::collection($addresses),
        ]);
    }

    /**
     * Store a newly created shipping address.
     */
    public function store(StoreShippingAddressRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;

        $address = ShippingAddress::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Shipping address created successfully',
            'data' => new ShippingAddressResource($address),
        ], 201);
    }

    /**
     * Display the specified shipping address.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $address = ShippingAddress::findOrFail($id);

        $this->authorize('view', $address);

        return response()->json([
            'success' => true,
            'data' => new ShippingAddressResource($address),
        ]);
    }

    /**
     * Update the specified shipping address.
     */
    public function update(UpdateShippingAddressRequest $request, string $id): JsonResponse
    {
        $address = ShippingAddress::findOrFail($id);

        $this->authorize('update', $address);

        $address->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Shipping address updated successfully',
            'data' => new ShippingAddressResource($address->fresh()),
        ]);
    }

    /**
     * Remove the specified shipping address.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $address = ShippingAddress::findOrFail($id);

        $this->authorize('delete', $address);

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shipping address deleted successfully',
        ]);
    }
}
