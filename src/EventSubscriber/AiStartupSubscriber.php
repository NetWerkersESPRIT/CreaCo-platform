<?php

namespace App\EventSubscriber;

use App\Service\AiProcessManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class AiStartupSubscriber implements EventSubscriberInterface
{
    private AiProcessManager $manager;

    public function __construct(AiProcessManager $manager)
    {
        $this->manager = $manager;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // start AI process on first request if not running
        if (!$event->isMainRequest()) {
            return;
        }
        if (!$this->manager->isRunning()) {
            $this->manager->start();
        }
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        // ensure AI process running when user logs in
        if (!$this->manager->isRunning()) {
            $this->manager->start();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }
}
