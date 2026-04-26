<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Security\ApiKeyToken;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 5)]
final class RateLimitListener
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        #[Autowire(service: 'limiter.api_external_client')]
        private readonly RateLimiterFactory $rateLimiterFactory,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token instanceof ApiKeyToken) {
            return;
        }

        $limit = $this->rateLimiterFactory->create($token->getApiKey())->consume(1);

        if ($limit->isAccepted()) {
            return;
        }

        $retryAfter = $limit->getRetryAfter()->getTimestamp() - time();
        throw new TooManyRequestsHttpException($retryAfter, 'Rate limit exceeded. Try again later.');
    }
}
