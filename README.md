# Generic Middleware Functional Test Module for Codeception

[![Build Status](https://travis-ci.org/LapazPhp/CodeceptionGenericMiddleware.svg?branch=master)](https://travis-ci.org/LapazPhp/CodeceptionGenericMiddleware)

Add MiddlewareContainer functional test module to your `finctional.suite.yml`:

```yaml
# Codeception Test Suite Configuration

class_name: FunctionalTester
modules:
    enabled:
        - \Lapaz\Codeception\Psr15\Module\MiddlewareContainer:
            containerFile: tests/_app/container.php
            processorName: middlewarePipe
            requestFactoryName: http.requestFactory
            responseFactoryName: http.responseFactory
            uriFactoryName: http.uriFactory
            streamFactoryName: http.streamFactory
            uploadedFileFactoryName: http.uploadedFileFactory
        - \Helper\Functional
        - Asserts
```

`tests/_app/container.php` example using Aura.Di:

```php
<?php
use Aura\Di\Container;
use Http\Factory\Diactoros\ResponseFactory;
use Http\Factory\Diactoros\ServerRequestFactory;
use Http\Factory\Diactoros\StreamFactory;
use Http\Factory\Diactoros\UploadedFileFactory;
use Http\Factory\Diactoros\UriFactory;

/** @var Container $di */
$di = require __DIR__ . '/../../config/container.php';

// Above app root middleware pipe might be defined as:
// $di->set('middlewarePipe', ...);

// Override other services here.

// Extra dependencies (if not defined)
$di->set('http.requestFactory', $di->lazyNew(ServerRequestFactory::class));
$di->set('http.responseFactory', $di->lazyNew(ResponseFactory::class));
$di->set('http.uriFactory', $di->lazyNew(UriFactory::class));
$di->set('http.streamFactory', $di->lazyNew(StreamFactory::class));
$di->set('http.uploadedFileFactory', $di->lazyNew(UploadedFileFactory::class));

return $di;
```

Then you can test the middleware as your application below:

```php
$I->amOnPage('/');
$I->see('Expected text');
```

Also you can access another service in the same container:

```php
$container = $I->getMiddlewareContainer();
$anotherService = $container->get('...');
```
