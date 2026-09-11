<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return <<<'HTML'
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Chetak API Server</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <meta http-equiv="refresh" content="3;url=http://localhost:5173" />
    </head>
    <body class="bg-gray-50 flex items-center justify-center min-h-screen font-sans">
        <div class="bg-white p-10 rounded-2xl shadow-xl border border-gray-100 text-center max-w-md">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">API is Online</h1>
            <p class="text-gray-500 mb-6">The Chetak Booking System backend is running successfully.</p>
            <p class="text-sm text-gray-400 mb-6">Redirecting you to the frontend application...</p>
            <a href="http://localhost:5173" class="inline-block w-full px-6 py-3 bg-teal-600 text-white font-medium rounded-lg shadow-sm hover:bg-teal-700 transition-colors">
                Go to Frontend Now
            </a>
        </div>
    </body>
    </html>
    HTML;
});
