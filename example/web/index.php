<?php
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\Server;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Stratigility\NoopFinalHandler;

require __DIR__ . '/../vendor/autoload.php';
$container = require __DIR__ . '/../config/container.php';

$server = Server::createServerFromRequest(
    $container->get('middlewarePipe'),
    ServerRequestFactory::fromGlobals()
);
$server->setEmitter(new SapiEmitter());
$server->listen(new NoopFinalHandler());
