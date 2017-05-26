<?php
namespace Lapaz\Codeception\GenericMiddleware\Lib\Connector;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class NoopFinalDelegateTest extends TestCase
{
    public function testProcess()
    {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);

        assert($response instanceof ResponseInterface);
        assert($request instanceof ServerRequestInterface);

        $delegate = new NoopFinalDelegate($response);
        $this->assertSame($response, $delegate->process($request));
    }
}
