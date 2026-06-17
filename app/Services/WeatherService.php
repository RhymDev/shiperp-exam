<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openweathermap.api_key');
        $this->baseUrl = config('services.openweathermap.base_url');
    }

    public function getWeatherData(string $city): array
    {
        try {
            $response = Http::timeout(10)
                ->withOptions([
                    'verify' => false, 
                ])
                ->get("{$this->baseUrl}/weather", [
                    'q' => $city,
                    'appid' => $this->apiKey,
                    'units' => 'metric'
                ]);

            if ($response->failed()) {
                if ($response->status() === 404) {
                    throw new \Exception("City '{$city}' not found", 404);
                }

                throw new \Exception("Failed to fetch weather data: " . $response->body(), $response->status());
            }

            $data = $response->json();

            return $this->formatWeatherData($data);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Weather API connection error: " . $e->getMessage());
            throw new \Exception("Unable to connect to weather service", 503);
        }
    }

    private function formatWeatherData(array $data): array
    {
        return [
            'city' => $data['name'] ?? 'Unknown',
            'temperature' => $data['main']['temp'] ?? null,
            'weather_description' => $data['weather'][0]['description'] ?? 'Unknown',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
