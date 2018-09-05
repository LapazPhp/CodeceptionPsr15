Web app

```console
$ php -S 0.0.0.0:8080 -t public/
```

Test

```console
$ vendor/bin/codecept build

Building Actor classes for suites: functional
 -> FunctionalTesterActions.php generated successfully. 0 methods added
\FunctionalTester includes modules: \Lapaz\Codeception\GenericMiddleware\Module\MiddlewareContainer, \Helper\Functional, Asserts


$ vendor/bin/codecept run functional --steps

Codeception PHP Testing Framework v2.4.5
Powered by PHPUnit 7.3.2 by Sebastian Bergmann and contributors.

Functional Tests (1) ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
HelloCest: Try to test
Signature: HelloCest:tryToTest
Test: tests/functional/HelloCest.php:tryToTest
Scenario --
 I am on page "/"
 I see "Hello"
 PASSED 

-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------


Time: 121 ms, Memory: 12.00MB

OK (1 test, 2 assertions)
```
