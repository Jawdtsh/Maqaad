<?php

namespace App\Services;

use App\Jobs\SendBookingConfirmation;
use App\Models\Booking;
use App\Models\CourseSession;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function bookSeat(int $studentId, int $sessionId): Booking
    {
        $session = CourseSession::with('course')->findOrFail($sessionId);

        $taken = Booking::where('session_id', $sessionId)
            ->where('status', 'confirmed')
            ->lockForUpdate()
            ->count();

        if ($taken >= $session->capacity){
        throw new \Exception('Session is full');
        }

        $wallet = Wallet::where('student_id', $studentId)->first();

        $price = $session->course->price;

        if ($wallet->balance < $price) {
            throw new \Exception('Insufficient balance');
        }

        DB::beginTransaction();

        $booking = Booking::create([
            'student_id' => $studentId,
            'session_id' => $sessionId,
            'status'     => 'confirmed'
        ]);

        $wallet->balance = $wallet->balance - $price;
        $wallet->save();

        SendBookingConfirmation::dispatch($booking);

        DB::commit();

        return $booking;
    }
}