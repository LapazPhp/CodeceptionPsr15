<?php

use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;
use Zend\HttpHandlerRunner\RequestHandlerRunner;
use Zend\Stratigility\Middleware\ErrorResponseGenerator;

require __DIR__ . '/../vendor/autoload.php';
$container = require __DIR__ . '/../config/container.php';

$runner = new RequestHandlerRunner(
    $container->get('rootRequestHandler'),
    new SapiEmitter(),
    [ServerRequestFactory::class, 'fromGlobals'],
    function ($e) {
        $generator = new ErrorResponseGenerator();
        return $generator($e, ServerRequestFactory::fromGlobals(), new Response());
    }
);
$runner->run();
