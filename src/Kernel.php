<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        parent::boot();

        // try to start AI backend as soon as kernel boots
        if ($this->getContainer()->has('App\Service\AiProcessManager')) {
            $manager = $this->getContainer()->get('App\Service\AiProcessManager');
            if (method_exists($manager, 'start')) {
                $manager->start();
            }
        }
    }
}
