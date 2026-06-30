<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Limite les tentatives de connexion (brute-force) sur /api/login_check :
 * au-delà de la limite (voir config/packages/rate_limiter.yaml), l'API répond
 * 429 Too Many Requests. La limite est appliquée par adresse IP.
 *
 * Le service "limiter.login" est injecté via l'autowiring nommé ($loginLimiter).
 */
final class LoginRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'limiter.login')]
        private readonly RateLimiterFactory $loginLimiter,
    ) {
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function getSubscribedEvents(): array
    {
        // Priorité élevée : on bloque avant que le firewall ne traite l'authentification.
        return [KernelEvents::REQUEST => ['onKernelRequest', 20]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ('POST' !== $request->getMethod() || '/api/login_check' !== $request->getPathInfo()) {
            return;
        }

        // Limite par compte ET par IP : brute-forcer un compte ne bloque pas
        // les autres utilisateurs partageant la même adresse IP.
        $email = '';
        try {
            $email = $request->getPayload()->getString('email');
        } catch (\Throwable) {
            // Corps non-JSON : on se rabat sur l'IP seule.
        }
        $key = ('' !== $email ? $email.'|' : '').($request->getClientIp() ?? 'unknown');

        $limiter = $this->loginLimiter->create($key);
        if (!$limiter->consume(1)->isAccepted()) {
            $event->setResponse(new JsonResponse(
                [
                    'title' => 'Trop de tentatives',
                    'detail' => 'Trop de tentatives de connexion. Veuillez réessayer dans un instant.',
                ],
                Response::HTTP_TOO_MANY_REQUESTS,
            ));
        }
    }
}
