<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Booking;
use App\Models\Station;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Get user's bookings
     */
    public function getUserBookings($userId)
    {
        try {
            // Ensure user can only access their own bookings or admin can access all
            if (Auth::id() != (int)$userId && !Auth::user()->is_admin) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $bookings = Booking::with('station')
                ->where('user_id', $userId)
                ->orderBy('start_time', 'desc')
                ->get()
                ->map(function ($booking) {
                    return [
                        'id' => $booking->id,
                        'station' => [
                            'id' => $booking->station->id ?? null,
                            'name' => $booking->station->name ?? 'Unknown Station',
                            'address' => $booking->station->address ?? 'Unknown Address',
                            'latitude' => $booking->station->latitude ?? null,
                            'longitude' => $booking->station->longitude ?? null,
                        ],
                        'start_time' => $booking->start_time,
                        'end_time' => $booking->end_time,
                        'date' => Carbon::parse($booking->start_time)->format('M d, Y'),
                        'time' => Carbon::parse($booking->start_time)->format('g:i A'),
                        'status' => $booking->status,
                        'total_amount' => $booking->total_amount ?? 0,
                        'duration_hours' => $booking->duration_hours,
                        'created_at' => $booking->created_at,
                        'updated_at' => $booking->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'bookings' => $bookings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch bookings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create Stripe payment session for booking
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            // Check if user can make more bookings (for free users)
            if (!$user->canMakeBooking()) {
                $remaining = $user->getRemainingBookings();
                return response()->json([
                    'success' => false,
                    'error' => $user->isPremium()
                        ? 'You have reached your booking limit.'
                        : "You have reached your weekly booking limit ({$remaining} remaining). Upgrade to Premium for unlimited bookings!",
                    'upgrade_required' => !$user->isPremium()
                ], 403);
            }

            $request->validate([
                'station_id' => 'required|exists:stations,id',
                'start_time' => 'required|date|after:now',
                'duration_hours' => 'required|numeric|min:0.5|max:24',
                'estimated_energy_needed' => 'nullable|numeric|min:1|max:200',
                'notes' => 'nullable|string|max:500'
            ]);

            $station = Station::findOrFail($request->station_id);
            $startTime = Carbon::parse($request->start_time);
            $durationHours = floatval($request->duration_hours);
            $endTime = $startTime->copy()->addHours($durationHours);

            // Check if station has available slots for the requested time
            $availableSlotsForTimeSlot = $station->getAvailableSlotsForTime($startTime, $endTime);

            if ($availableSlotsForTimeSlot <= 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'The charging station is fully reserved already'
                ], 422);
            }

            // Calculate cost using station pricing or default
            $hourlyRate = $station->pricing_per_hour ?? 5.0; // Default $5/hour
            $baseCost = $durationHours * $hourlyRate;

            // Apply premium discount
            $discountPercentage = $user->getDiscountPercentage();
            $totalCost = $baseCost * (1 - $discountPercentage / 100);

            // Create Stripe payment session
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $checkoutSession = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => 'EV Charging Session - ' . $station->name,
                                'description' => 'Charging session from ' . $startTime->format('M j, Y g:i A') . ' for ' . $durationHours . ' hours',
                            ],
                            'unit_amount' => intval($totalCost * 100), // Stripe expects cents
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel'),
                'metadata' => [
                    'user_id' => $user->id,
                    'station_id' => $request->station_id,
                    'start_time' => $startTime->toISOString(),
                    'duration_hours' => $durationHours,
                    'estimated_energy_needed' => $request->estimated_energy_needed,
                    'notes' => $request->notes,
                    'discount_percentage' => $discountPercentage,
                ],
            ]);

            return response()->json([
                'success' => true,
                'payment_required' => true,
                'stripe_checkout_url' => $checkoutSession->url,
                'session_id' => $checkoutSession->id,
                'booking_details' => [
                    'station' => [
                        'id' => $station->id,
                        'name' => $station->name,
                        'address' => $station->address,
                    ],
                    'start_time' => $startTime->format('M j, Y g:i A'),
                    'duration_hours' => $durationHours,
                    'total_amount' => $totalCost,
                    'discount_applied' => $discountPercentage > 0 ? $discountPercentage : null,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create payment session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle successful payment and create booking
     */
    public function handlePaymentSuccess(Request $request)
    {
        try {
            $sessionId = $request->get('session_id');

            if (!$sessionId) {
                return redirect()->route('dashboard')->with('error', 'Invalid payment session.');
            }

            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            // Retrieve the checkout session
            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return redirect()->route('dashboard')->with('error', 'Payment not completed.');
            }

            // Extract booking data from metadata
            $metadata = $session->metadata;
            $user = \App\Models\User::findOrFail($metadata->user_id);
            $station = Station::findOrFail($metadata->station_id);
            $startTime = Carbon::parse($metadata->start_time);
            $durationHours = floatval($metadata->duration_hours); // Convert string to float
            $endTime = $startTime->copy()->addHours($durationHours);

            // Double-check slot availability
            $availableSlotsForTimeSlot = $station->getAvailableSlotsForTime($startTime, $endTime);

            if ($availableSlotsForTimeSlot <= 0) {
                return redirect()->route('dashboard')->with('error', 'Sorry, the charging station is now fully booked for your selected time slot. Your payment will be refunded.');
            }

            // Calculate cost using the same logic as before
            $hourlyRate = $station->pricing_per_hour ?? 5.0;
            $baseCost = $durationHours * $hourlyRate;
            $discountPercentage = floatval($metadata->discount_percentage ?? 0);
            $totalCost = $baseCost * (1 - $discountPercentage / 100);

            // Create the booking
            $booking = Booking::create([
                'user_id' => $metadata->user_id,
                'station_id' => $metadata->station_id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_hours' => $durationHours,
                'estimated_energy_needed' => floatval($metadata->estimated_energy_needed ?? 0),
                'total_amount' => $totalCost,
                'status' => 'confirmed', // Set as confirmed since payment is complete
                'notes' => $metadata->notes ?? null,
                'payment_session_id' => $sessionId,
            ]);

            // Update available slots based on current bookings
            $station->updateAvailableSlots();

            // Increment user's weekly booking count
            $user->incrementWeeklyBookings();

            return redirect()->route('dashboard')->with('success', 'Payment successful! Your charging session has been booked.');

        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Failed to process payment: ' . $e->getMessage());
        }
    }

    /**
     * Handle payment success page redirect
     */
    public function paymentSuccess(Request $request)
    {
        $sessionId = $request->get('session_id');

        if ($sessionId) {
            // Process the successful payment
            return $this->handlePaymentSuccess($request);
        }

        // Show success page without specific booking details
        return view('payment.success');
    }

    /**
     * Cancel a booking and free up the slot
     */
    public function cancel(Request $request, $id)
    {
        Log::info('Booking cancel called', ['id' => $id, 'user' => Auth::id()]);

        try {
            $booking = Booking::findOrFail($id);

            // Ensure user can only cancel their own bookings
            if (Auth::id() != $booking->user_id && !Auth::user()->is_admin) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Only allow cancellation of pending or approved bookings
            if (!in_array($booking->status, ['pending', 'approved', 'confirmed'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Only pending, approved, or confirmed bookings can be cancelled.'
                ], 422);
            }

            // Update booking status
            $booking->update(['status' => 'cancelled']);

            // Update available slots for the station
            $booking->station->updateAvailableSlots();

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to cancel booking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a booking (modify duration/time or status)
     */
    public function update(Request $request, $id)
    {
        Log::info('Booking update called', ['id' => $id, 'data' => $request->all()]);

        try {
            $booking = Booking::findOrFail($id);

            // Ensure user can only modify their own bookings
            if (Auth::id() != $booking->user_id && !Auth::user()->is_admin) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Handle status update (mark as completed)
            if ($request->has('status')) {
                $request->validate([
                    'status' => 'required|in:completed',
                ]);

                // Only allow status change to completed for confirmed/approved bookings
                if (!in_array($booking->status, ['confirmed', 'approved'])) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Only confirmed or approved bookings can be marked as completed.'
                    ], 422);
                }

                $booking->update(['status' => 'completed']);

                return response()->json([
                    'success' => true,
                    'message' => 'Booking marked as completed successfully!',
                    'booking' => [
                        'id' => $booking->id,
                        'status' => $booking->status,
                        'date' => Carbon::parse($booking->start_time)->format('M d, Y'),
                        'time' => Carbon::parse($booking->start_time)->format('g:i A'),
                    ]
                ]);
            }

            // Handle time and duration update
            // Only allow modification of pending bookings
            if ($booking->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'error' => 'Only pending bookings can be modified. Approved or confirmed bookings cannot be changed.'
                ], 422);
            }

            try {
                $request->validate([
                    'start_time' => 'required|date|after:now',
                    'duration_hours' => 'required|numeric|min:0.5|max:24',
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all())
                ], 422);
            }

            $station = $booking->station;
            $newStartTime = Carbon::parse($request->start_time);
            $newDurationHours = floatval($request->duration_hours);
            $newEndTime = $newStartTime->copy()->addHours($newDurationHours);

            // Check if the new time slot is available (excluding current booking)
            $conflictingBookings = Booking::where('station_id', $station->id)
                ->where('id', '!=', $booking->id) // Exclude current booking
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($newStartTime, $newEndTime) {
                    $query->whereBetween('start_time', [$newStartTime, $newEndTime])
                          ->orWhereBetween('end_time', [$newStartTime, $newEndTime])
                          ->orWhere(function ($q) use ($newStartTime, $newEndTime) {
                              $q->where('start_time', '<=', $newStartTime)
                                ->where('end_time', '>=', $newEndTime);
                          });
                })
                ->count();

            if ($conflictingBookings >= $station->total_slots) {
                return response()->json([
                    'success' => false,
                    'error' => 'The selected time slot is not available. Please choose a different time.'
                ], 422);
            }

            // Calculate new cost
            $user = Auth::user();
            $hourlyRate = $station->pricing_per_hour ?? 5.0;
            $baseCost = $newDurationHours * $hourlyRate;
            $discountPercentage = $user->getDiscountPercentage();
            $totalCost = $baseCost * (1 - $discountPercentage / 100);

            // Update the booking
            $booking->update([
                'start_time' => $newStartTime,
                'end_time' => $newEndTime,
                'duration_hours' => $newDurationHours,
                'total_amount' => $totalCost,
            ]);

            // Update available slots for the station
            $station->updateAvailableSlots();

            return response()->json([
                'success' => true,
                'message' => 'Booking updated successfully!',
                'booking' => [
                    'id' => $booking->id,
                    'station' => [
                        'id' => $station->id,
                        'name' => $station->name,
                        'address' => $station->address,
                    ],
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'date' => Carbon::parse($booking->start_time)->format('M d, Y'),
                    'time' => Carbon::parse($booking->start_time)->format('g:i A'),
                    'status' => $booking->status,
                    'total_amount' => $booking->total_amount,
                    'duration_hours' => $booking->duration_hours,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Booking update failed', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update booking: ' . $e->getMessage()
            ], 500);
        }
    }
}
