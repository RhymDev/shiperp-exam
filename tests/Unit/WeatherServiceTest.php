<?php

namespace Tests\Unit;

use App\Services\WeatherService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    private WeatherService $weatherService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->weatherService = new WeatherService();
    }


    public function test_weather_service_formats_response_correctly(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response([
                'name' => 'New York',
                'main' => [
                    'temp' => 20.5,
                    'humidity' => 65,
                ],
                'weather' => [
                    [
                        'description' => 'cloudy',
                        'icon' => '04d',
                    ],
                ],
                'dt' => 1234567890,
            ], 200),
        ]);

        $result = $this->weatherService->getWeatherData('New York');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('city', $result);
        $this->assertArrayHasKey('temperature', $result);
        $this->assertArrayHasKey('weather_description', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertEquals('New York', $result['city']);
        $this->assertEquals(20.5, $result['temperature']);
        $this->assertEquals('cloudy', $result['weather_description']);
    }

    public function test_weather_service_throws_exception_on_city_not_found(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response([], 404),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("City 'UnknownCity' not found");
        $this->expectExceptionCode(404);

        $this->weatherService->getWeatherData('UnknownCity');
    }

    public function test_weather_service_throws_exception_on_api_error(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response(['error' => 'API Error'], 500),
        ]);

        $this->expectException(\Exception::class);

        $this->weatherService->getWeatherData('TestCity');
    }

    public function test_weather_service_throws_exception_on_connection_error(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Timeout');
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to connect to weather service');
        $this->expectExceptionCode(503);

        $this->weatherService->getWeatherData('TestCity');
    }
}
