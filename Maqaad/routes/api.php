<?php

use App\Modules\Booking\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::post('/sessions/{id}/bookings',[BookingController::class, 'store']
)->whereNumber('id');