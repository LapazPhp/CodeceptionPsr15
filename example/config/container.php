<?php

use Acme\App\HelloHandler;
use Acme\App\HelloMiddleware;
use Aura\Di\ContainerBuilder;
use Zend\Stratigility\Middleware\RequestHandlerMiddleware;
use Zend\Stratigility\MiddlewarePipe;

$containerBuilder = new ContainerBuilder();
$di = $containerBuilder->newInstance();

$di->set('rootRequestHandler', $di->lazy(function () use ($di) {
    $pipe = $di->newInstance(MiddlewarePipe::class);
    $pipe->pipe($di->get(HelloMiddleware::class));
    $pipe->pipe($di->newInstance(RequestHandlerMiddleware::class, [
        'handler' => $di->get(HelloHandler::class)
    ]));
    return $pipe;
}));

$di->set(HelloMiddleware::class, $di->lazyNew(HelloMiddleware::class));

$di->set(HelloHandler::class, $di->lazyNew(HelloHandler::class));

return $di;
