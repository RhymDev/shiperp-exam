<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WeatherController extends Controller
{
    private WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }


    public function getWeather(string $city): JsonResponse
    {
        try {
            if (empty(trim($city))) {
                return response()->json([
                    'error' => 'City name is required'
                ], 400);
            }

            $weatherData = $this->weatherService->getWeatherData($city);

            return response()->json([
                ...$weatherData,
                'source' => 'external'
            ], 200);
        } catch (\Exception $e) {
            Log::error("Weather API error for city '{$city}': " . $e->getMessage());

            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

            return response()->json([
                'error' => $e->getMessage()
            ], $statusCode);
        }
    }


    public function getCachedWeather(string $city): JsonResponse
    {
        try {
            if (empty(trim($city))) {
                return response()->json([
                    'error' => 'City name is required'
                ], 400);
            }

            $cacheKey = $this->getCacheKey($city);

            if (Cache::has($cacheKey)) {
                $weatherData = Cache::get($cacheKey);

                return response()->json([
                    ...$weatherData,
                    'source' => 'cache'
                ], 200);
            }

            $weatherData = $this->weatherService->getWeatherData($city);

            Cache::put($cacheKey, $weatherData, now()->addMinutes(10));

            return response()->json([
                ...$weatherData,
                'source' => 'external'
            ], 200);
        } catch (\Exception $e) {
            Log::error("Cached weather API error for city '{$city}': " . $e->getMessage());

            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

            return response()->json([
                'error' => $e->getMessage()
            ], $statusCode);
        }
    }


    private function getCacheKey(string $city): string
    {
        return 'weather_' . strtolower(trim($city));
    }
}
