# Generic Middleware Functional Test Module for Codeception

[![Build Status](https://travis-ci.org/LapazPhp/CodeceptionPsr15.svg?branch=master)](https://travis-ci.org/LapazPhp/CodeceptionPsr15)

Add Psr15 functional test module to your `finctional.suite.yml`:

```yaml
# Codeception Test Suite Configuration

class_name: FunctionalTester
modules:
    enabled:
        - \Lapaz\Codeception\Psr15\Module\Psr15:
            requestHandler: tests/_app/handler.php
        - \Helper\Functional
        - Asserts
```

`tests/_app/handler.php` example using PSR-11 DI container:

```php
<?php
/** @var \Psr\Container\ContainerInterface $di */
$di = require __DIR__ . '/../../config/container.php';

return $di->get('rootRequestHandler');
```

Then you can test the middleware as your application below:

```php
$I->amOnPage('/');
$I->see('Expected text');
```
