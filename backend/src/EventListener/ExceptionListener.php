<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ExceptionListener
{
    public function __construct(
        #[Autowire('%kernel.debug%')] private readonly bool $debug,
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $isHttp = $exception instanceof HttpExceptionInterface;

        $statusCode = $isHttp
            ? $exception->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        $headers = $isHttp ? $exception->getHeaders() : [];

        $message = ($isHttp || $this->debug)
            ? $exception->getMessage()
            : 'Internal server error';

        $event->setResponse(new JsonResponse(
            ['error' => $message],
            $statusCode,
            $headers,
        ));
    }
}
