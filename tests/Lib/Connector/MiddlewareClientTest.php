<?php
namespace Lapaz\Codeception\GenericMiddleware\Lib\Connector;

use Http\Factory\Diactoros\ResponseFactory;
use Http\Factory\Diactoros\ServerRequestFactory;
use Http\Factory\Diactoros\StreamFactory;
use Http\Factory\Diactoros\UploadedFileFactory;
use Http\Factory\Diactoros\UriFactory;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\BrowserKit\Request;

class MiddlewareClientTest extends \PHPUnit_Framework_TestCase
{
    public function testMiddlewareInterface()
    {
        $connector = new MiddlewareClient();

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

        $processor = $this->createMock(MiddlewareInterface::class);
        $processor->method('process')->willReturn($response);

        assert($processor instanceof MiddlewareInterface);
        $connector->setProcessor($processor);

        $browserRequest = new Request('/hello', 'GET');
        $browserResponse = $connector->doRequest($browserRequest);

        $this->assertEquals(200, $browserResponse->getStatus());
        $this->assertEquals('text/plain', $browserResponse->getHeader('content-type'));
        $this->assertEquals('Hello', $browserResponse->getContent());
    }

    public function testMiddlewareFunction()
    {
        $connector = new MiddlewareClient();

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

        $outResponse = $response;
        $processor = function ($request, $inResponse, $next) use ($outResponse) {
            assert($request instanceof RequestInterface);
            assert($inResponse instanceof ResponseInterface);
            assert(is_callable($next));
            return $outResponse;
        };

        $connector->setProcessor($processor);

        $browserRequest = new Request('/hello', 'GET');
        $browserResponse = $connector->doRequest($browserRequest);

        $this->assertEquals(200, $browserResponse->getStatus());
        $this->assertEquals('text/plain', $browserResponse->getHeader('content-type'));
        $this->assertEquals('Hello', $browserResponse->getContent());
    }

    public function testFactoryFunction()
    {
        $connector = new MiddlewareClient();

        $connector->setFactories(
            function ($server = []) {
                return (new ServerRequestFactory())->createServerRequestFromArray($server);
            },
            function ($code = 200) {
                return (new ResponseFactory())->createResponse($code);
            },
            function ($uri = '') {
                return (new UriFactory())->createUri($uri);
            },
            function ($resource) {
                $f = (new StreamFactory());
                return $f->createStreamFromResource($resource);
            },
            function (
                $file,
                $size = null,
                $error = \UPLOAD_ERR_OK,
                $clientFilename = null,
                $clientMediaType = null
            ) {
                return (new UploadedFileFactory())->createUploadedFile(
                    $file,
                    $size,
                    $error,
                    $clientFilename,
                    $clientMediaType
                );
            }
        );

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn('Hello');
        $response->method('getHeaders')->willReturn([
            'content-type' => 'text/plain',
        ]);
        $response->method('getStatusCode')->willReturn(200);

        $processor = $this->createMock(MiddlewareInterface::class);
        $processor->method('process')->willReturn($response);

        assert($processor instanceof MiddlewareInterface);
        $connector->setProcessor($processor);

        $browserRequest = new Request('/hello', 'GET');
        $browserResponse = $connector->doRequest($browserRequest);

        $this->assertEquals(200, $browserResponse->getStatus());
        $this->assertEquals('text/plain', $browserResponse->getHeader('content-type'));
        $this->assertEquals('Hello', $browserResponse->getContent());
    }
}
