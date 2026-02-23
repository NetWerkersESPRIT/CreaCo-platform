<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getWeatherForecast(): array
    {
        $url = 'https://api.open-meteo.com/v1/forecast?latitude=36.819&longitude=10.1658&hourly=temperature_2m,rain&forecast_days=16';

        try {
            $response = $this->httpClient->request('GET', $url);
            return $response->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
}
