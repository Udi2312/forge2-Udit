<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'PulseDesk API',
        'version' => '1.0.0',
    ]);
});
