<?php
use Zend\Stratigility\MiddlewarePipe;

$containerBuilder = new Aura\Di\ContainerBuilder();
$di = $containerBuilder->newInstance();

$di->set('middlewarePipe', $di->lazy(function () use ($di) {
    $pipe = $di->newInstance(MiddlewarePipe::class);
    $pipe->pipe($di->get('middleware.hello'));
    return $pipe;
}));

$di->set('middleware.hello', $di->lazyNew(\Acme\App\HelloMiddleware::class));

return $di;
