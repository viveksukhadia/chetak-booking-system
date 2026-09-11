<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Chetak Booking System API',
        'status' => 'online',
        'version' => '1.0.0',
        'frontend_url' => 'http://localhost:5173'
    ]);
});
