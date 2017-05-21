<?php
use Aura\Di\Container;
use Http\Factory\Diactoros\ResponseFactory;
use Http\Factory\Diactoros\ServerRequestFactory;
use Http\Factory\Diactoros\StreamFactory;
use Http\Factory\Diactoros\UploadedFileFactory;
use Http\Factory\Diactoros\UriFactory;

/** @var Container $di */
$di = require __DIR__ . '/../../config/container.php';

// Extra dependencies
$di->set('http.requestFactory', $di->lazyNew(ServerRequestFactory::class));
$di->set('http.responseFactory', $di->lazyNew(ResponseFactory::class));
$di->set('http.uriFactory', $di->lazyNew(UriFactory::class));
$di->set('http.streamFactory', $di->lazyNew(StreamFactory::class));
$di->set('http.uploadedFileFactory', $di->lazyNew(UploadedFileFactory::class));

return $di;
