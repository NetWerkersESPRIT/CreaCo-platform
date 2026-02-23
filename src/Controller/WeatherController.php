<?php

namespace App\Controller;

use App\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class WeatherController extends AbstractController
{
    #[Route('/api/weather', name: 'api_weather')]
    public function getWeather(WeatherService $weatherService): JsonResponse
    {
        $data = $weatherService->getWeatherForecast();
        return new JsonResponse($data);
    }
}
