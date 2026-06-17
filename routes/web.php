<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;

Route::get('/', function () {
    return view('welcome');
});

// Weather API endpoints with rate limiting (60 requests per minute per IP)
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/weather/{city}', [WeatherController::class, 'getWeather']);
    Route::get('/weather/{city}/cached', [WeatherController::class, 'getCachedWeather']);
});
