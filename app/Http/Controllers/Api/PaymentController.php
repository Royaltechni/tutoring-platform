<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request, $bookingUuid)
    {
        $booking = Booking::where('uuid', $bookingUuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json($booking->payments);
    }

    public function store(Request $request, $bookingUuid)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'payment_provider' => 'required|string',
            'payment_method' => 'nullable|string',
            'external_transaction_id' => 'nullable|string',
        });

        $booking = Booking::where('uuid', $bookingUuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $payment = $booking->payments()->create([
            'amount' => $request->amount,
            'currency' => $request->currency,
            'payment_provider' => $request->payment_provider,
            'payment_method' => $request->payment_method,
            'status' => 'pending',
            'external_transaction_id' => $request->external_transaction_id,
        ]);

        return response()->json([
            'message' => 'Payment record created',
            'payment' => $payment,
        ], 201);
    }
}
