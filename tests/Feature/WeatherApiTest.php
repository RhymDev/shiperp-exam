<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_get_weather_returns_successful_response(): void
    {
        // Mock the HTTP response
        Http::fake([
            'api.openweathermap.org/*' => Http::response([
                'name' => 'London',
                'main' => [
                    'temp' => 15.5,
                ],
                'weather' => [
                    [
                        'description' => 'clear sky',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/weather/London');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'city',
                'temperature',
                'weather_description',
                'timestamp',
                'source',
            ])
            ->assertJson([
                'city' => 'London',
                'temperature' => 15.5,
                'weather_description' => 'clear sky',
                'source' => 'external',
            ]);
    }

    public function test_get_weather_returns_error_for_invalid_city(): void
    {
        // Mock 404 response from OpenWeatherMap
        Http::fake([
            'api.openweathermap.org/*' => Http::response([], 404),
        ]);

        $response = $this->getJson('/weather/InvalidCityName123');

        $response->assertStatus(404)
            ->assertJson([
                'error' => "City 'InvalidCityName123' not found",
            ]);
    }

    public function test_get_weather_handles_connection_error(): void
    {
        // Mock connection exception
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
        });

        $response = $this->getJson('/weather/London');

        $response->assertStatus(503)
            ->assertJson([
                'error' => 'Unable to connect to weather service',
            ]);
    }

    public function test_get_cached_weather_fetches_from_external_on_first_call(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response([
                'name' => 'Paris',
                'main' => [
                    'temp' => 18.2,
                ],
                'weather' => [
                    [
                        'description' => 'partly cloudy',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/weather/Paris/cached');

        $response->assertStatus(200)
            ->assertJson([
                'city' => 'Paris',
                'temperature' => 18.2,
                'weather_description' => 'partly cloudy',
                'source' => 'external',
            ]);

        // Verify data is cached
        $this->assertTrue(Cache::has('weather_paris'));
    }

    /**
     * Test cached weather endpoint returns data from cache on subsequent calls
     */
    public function test_get_cached_weather_returns_from_cache_on_subsequent_calls(): void
    {
        // Pre-populate cache
        $cachedData = [
            'city' => 'Berlin',
            'temperature' => 12.5,
            'weather_description' => 'rainy',
            'timestamp' => now()->toIso8601String(),
        ];

        Cache::put('weather_berlin', $cachedData, now()->addMinutes(10));

        // Make request without mocking HTTP (if it calls external API, it will fail)
        $response = $this->getJson('/weather/Berlin/cached');

        $response->assertStatus(200)
            ->assertJson([
                'city' => 'Berlin',
                'temperature' => 12.5,
                'weather_description' => 'rainy',
                'source' => 'cache',
            ]);
    }

    /**
     * Test cache expires after 10 minutes
     */
    public function test_cache_expires_after_ten_minutes(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response([
                'name' => 'Tokyo',
                'main' => [
                    'temp' => 22.0,
                ],
                'weather' => [
                    [
                        'description' => 'sunny',
                    ],
                ],
            ], 200),
        ]);

        // First call - should cache the data
        $this->getJson('/weather/Tokyo/cached');

        // Verify cache exists
        $this->assertTrue(Cache::has('weather_tokyo'));

        // Travel forward 11 minutes
        $this->travel(11)->minutes();

        // Verify cache has expired
        $this->assertFalse(Cache::has('weather_tokyo'));
    }

    /**
     * Test empty city name returns error
     */
    public function test_empty_city_name_returns_error(): void
    {
        // Using %20 for URL-encoded space
        $response = $this->getJson('/weather/%20');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'City name is required',
            ]);
    }

    /**
     * Test cached endpoint handles API errors gracefully
     */
    public function test_cached_weather_handles_api_errors(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response([], 500),
        ]);

        $response = $this->getJson('/weather/TestCity/cached');

        $response->assertStatus(500)
            ->assertJsonStructure(['error']);
    }

    /**
     * Test non-existent routes return JSON error response
     */
    public function test_nonexistent_routes_return_json_error(): void
    {
        $response = $this->getJson('/nonexistent-route');

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Route not found',
                'message' => 'The requested endpoint does not exist',
            ]);
    }

    public function test_rate_limiting_is_enforced(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response([
                'name' => 'London',
                'main' => ['temp' => 15.5],
                'weather' => [['description' => 'clear sky']],
            ], 200),
        ]);

        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/weather/London');
            
            if ($i < 60) {
                $response->assertStatus(200);
            } else {
                $response->assertStatus(429);
            }
        }
    }
}
