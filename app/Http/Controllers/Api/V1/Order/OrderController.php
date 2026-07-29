<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\OrderRequest;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    /**
     * Display a listing of orders for the given restaurant.
     */
    public function index(Restaurant $restaurant): JsonResponse
    {
        $orders = $restaurant->orders()
            ->with('items.product', 'items.modifiers.modifierOption')
            ->latest('id')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'message' => 'No data found',
            ]);
        }

        return response()->json([
            'data' => $orders,
        ]);
    }

    /**
     * Store a newly created order for the given restaurant.
     */
    public function store(OrderRequest $request, Restaurant $restaurant): JsonResponse
    {
        $validated = $request->validated();

        $orderData = collect($validated)->except('items')->toArray();
        $orderData['order_number'] ??= $this->generateOrderNumber($restaurant);

        $order = $restaurant->orders()->create($orderData);

        if ($items = $validated['items'] ?? []) {
            foreach ($items as $itemData) {
                $item = $order->items()->create([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'subtotal' => $itemData['subtotal'] ?? round($itemData['unit_price'] * $itemData['quantity'], 2),
                    'notes' => $itemData['notes'] ?? null,
                ]);

                if ($modifiers = $itemData['modifiers'] ?? []) {
                    foreach ($modifiers as $modData) {
                        $item->modifiers()->create([
                            'modifier_option_id' => $modData['modifier_option_id'],
                            'price' => $modData['price'] ?? 0,
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'data' => $order->load('items.modifiers.modifierOption'),
            'message' => 'Order created successfully.',
        ], 201);
    }

    /**
     * Display the specified order.
     */
    public function show(Restaurant $restaurant, Order $order): JsonResponse
    {
        abort_if($order->restaurant_id !== $restaurant->id, 404);

        return response()->json([
            'data' => $order->load('items.modifiers.modifierOption'),
        ]);
    }

    /**
     * Update the specified order.
     */
    public function update(OrderRequest $request, Restaurant $restaurant, Order $order): JsonResponse
    {
        abort_if($order->restaurant_id !== $restaurant->id, 404);

        $validated = $request->validated();

        $order->update($validated);

        return response()->json([
            'data' => $order->fresh()->load('items.modifiers.modifierOption'),
            'message' => 'Order updated successfully.',
        ]);
    }

    /**
     * Remove the specified order from storage.
     */
    public function destroy(Restaurant $restaurant, Order $order): JsonResponse
    {
        abort_if($order->restaurant_id !== $restaurant->id, 404);

        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully.',
        ]);
    }

    /**
     * Generate a unique order number for the restaurant.
     */
    private function generateOrderNumber(Restaurant $restaurant): string
    {
        $lastOrder = $restaurant->orders()->latest('id')->first();
        $nextNumber = $lastOrder ? (int) filter_var($lastOrder->order_number, FILTER_SANITIZE_NUMBER_INT) + 1 : 1;

        return 'ORD-' . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
