<?php
namespace Lapaz\Codeception\Psr15\Lib\Connector;

use Http\Factory\Diactoros\ResponseFactory;
use Http\Factory\Diactoros\ServerRequestFactory;
use Http\Factory\Diactoros\StreamFactory;
use Http\Factory\Diactoros\UploadedFileFactory;
use Http\Factory\Diactoros\UriFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\BrowserKit\Request;

class Psr15ClientTest extends TestCase
{
    public function testRequestHandlerInterface()
    {
        $connector = new Psr15Client();

        $connector->setFactories(
            new ServerRequestFactory(),
            new ResponseFactory(),
            new UriFactory(),
            new StreamFactory(),
            new UploadedFileFactory()
        );

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn('Hello');
        $response->method('getHeaders')->willReturn([
            'content-type' => 'text/plain',
        ]);
        $response->method('getStatusCode')->willReturn(200);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $connector->setRequestHandler($handler);

        $browserRequest = new Request('/hello', 'GET');
        $browserResponse = $connector->doRequest($browserRequest);

        $this->assertEquals(200, $browserResponse->getStatus());
        $this->assertEquals('text/plain', $browserResponse->getHeader('content-type'));
        $this->assertEquals('Hello', $browserResponse->getContent());
    }
}
