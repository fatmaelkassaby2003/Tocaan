<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(protected PaymentGatewayManager $gatewayManager) {}

    public function index(Request $request)
    {
        $query = Payment::with('order')
            ->whereHas('order', fn($q) => $q->where('user_id', auth()->id()));

        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        $payments = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $payments,
        ]);
    }

    public function process(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id'       => 'required|exists:orders,id',
            'payment_method' => 'required|in:' . implode(',', $this->gatewayManager->getAvailableGateways()),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $order = Order::findOrFail($request->order_id);

        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (!$order->isConfirmed()) {
            return response()->json([
                'success' => false,
                'message' => 'Payments can only be processed for confirmed orders',
            ], 422);
        }

        $gateway  = $this->gatewayManager->gateway($request->payment_method);
        $response = $gateway->process([
            'amount'   => $order->total,
            'order_id' => $order->id,
        ]);

        $payment = Payment::create([
            'payment_id'       => Str::uuid(),
            'order_id'         => $order->id,
            'status'           => $response['success'] ? 'successful' : 'failed',
            'payment_method'   => $request->payment_method,
            'amount'           => $order->total,
            'gateway_response' => $response,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data'    => $payment->load('order'),
        ], 201);
    }

    public function show(Payment $payment)
    {
        if ($payment->order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $payment->load('order'),
        ]);
    }
}