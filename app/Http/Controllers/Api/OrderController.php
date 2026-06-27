<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items', 'payments'])
            ->where('user_id', auth()->id());

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $orders,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.product_name'     => 'required|string|max:255',
            'items.*.quantity'         => 'required|integer|min:1',
            'items.*.price'            => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $total = collect($request->items)->sum(fn($item) => $item['quantity'] * $item['price']);

        $order = Order::create([
            'user_id' => auth()->id(),
            'status'  => 'pending',
            'total'   => $total,
            'notes'   => $request->notes,
        ]);

        foreach ($request->items as $item) {
            $order->items()->create([
                'product_name' => $item['product_name'],
                'quantity'     => $item['quantity'],
                'price'        => $item['price'],
                'subtotal'     => $item['quantity'] * $item['price'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data'    => $order->load('items'),
        ], 201);
    }

    public function show(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $order->load(['items', 'payments']),
        ]);
    }

    public function update(Request $request, Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status'                   => 'sometimes|in:pending,confirmed,cancelled',
            'notes'                    => 'nullable|string',
            'items'                    => 'sometimes|array|min:1',
            'items.*.product_name'     => 'required_with:items|string|max:255',
            'items.*.quantity'         => 'required_with:items|integer|min:1',
            'items.*.price'            => 'required_with:items|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $order->update($request->only('status', 'notes'));

        if ($request->has('items')) {
            $order->items()->delete();
            $total = 0;

            foreach ($request->items as $item) {
                $subtotal = $item['quantity'] * $item['price'];
                $total += $subtotal;
                $order->items()->create([
                    'product_name' => $item['product_name'],
                    'quantity'     => $item['quantity'],
                    'price'        => $item['price'],
                    'subtotal'     => $subtotal,
                ]);
            }

            $order->update(['total' => $total]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data'    => $order->load('items'),
        ]);
    }

    public function destroy(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($order->hasPayments()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete order with associated payments',
            ], 422);
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully',
        ]);
    }
}