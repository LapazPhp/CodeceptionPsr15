<?php
namespace Lapaz\Codeception\Psr15\Lib\Connector;

use Codeception\Configuration;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;

class MiddlewareClient extends Client
{
    /**
     * @var MiddlewareInterface|callable
     */
    protected $processor;

    /**
     * @var ServerRequestFactoryInterface|callable
     */
    protected $requestFactory;

    /**
     * @var ResponseFactoryInterface|callable
     */
    protected $responseFactory;

    /**
     * @var UriFactoryInterface|callable
     */
    protected $uriFactory;

    /**
     * @var StreamFactoryInterface|callable
     */
    protected $streamFactory;

    /**
     * @var UploadedFileFactoryInterface|callable
     */
    protected $uploadedFileFactory;

    /**
     * @param callable|ServerRequestFactoryInterface $requestFactory
     * @param callable|ResponseFactoryInterface $responseFactory
     * @param callable|UriFactoryInterface $uriFactory
     * @param callable|StreamFactoryInterface $streamFactory
     * @param callable|UploadedFileFactoryInterface $uploadedFileFactory
     */
    public function setFactories($requestFactory, $responseFactory, $uriFactory, $streamFactory, $uploadedFileFactory)
    {
        $this->requestFactory = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->uriFactory = $uriFactory;
        $this->streamFactory = $streamFactory;
        $this->uploadedFileFactory = $uploadedFileFactory;
    }

    /**
     * @param MiddlewareInterface|callable $processor
     */
    public function setProcessor($processor)
    {
        $this->processor = $processor;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function doRequest($request)
    {
        $serverParams = $request->getServer();
        if (!isset($serverParams['SCRIPT_NAME'])) {
            //required by WhoopsErrorHandler
            $serverParams['SCRIPT_NAME'] = 'Codeception';
        }

        $method = $request->getMethod();
        $uri = $request->getUri();

        $queryParams = [];
        $postParams = [];
        $queryString = parse_url($request->getUri(), PHP_URL_QUERY);
        if ($queryString != '') {
            parse_str($queryString, $queryParams);
        }
        if ($method !== 'GET') {
            $postParams = $request->getParameters();
        }

        $inputStream = fopen('php://memory', 'r+');
        $content = $request->getContent();
        if ($content !== null) {
            fwrite($inputStream, $content);
            rewind($inputStream);
        }

        $psr7Request = $this->createRequest($method, $this->createUri($uri), $serverParams)
            ->withBody($this->createStream($inputStream))
            ->withQueryParams($queryParams)
            ->withParsedBody($postParams)
            ->withCookieParams($request->getCookies())
        ;

        foreach ($this->extractHeaders($request) as $k => $v) {
            $psr7Request = $psr7Request->withHeader($k, $v);
        }

        $uploadedFiles = $request->getFiles();
        if (count($uploadedFiles) > 0) {
            $psr7Request = $psr7Request->withUploadedFiles($uploadedFiles);
        }

        $psr7ResponsePrototype = $this->createResponse();

        $cwd = getcwd();
        chdir(Configuration::projectDir());

        if ($this->processor instanceof MiddlewareInterface) {
            $finalDelegate = new NoopFinalHandler($psr7ResponsePrototype);
            $psr7Response = $this->processor->process($psr7Request, $finalDelegate);
        } else {
            $psr7Response = call_user_func($this->processor, $psr7Request, $psr7ResponsePrototype, function ($request, $response) {
                $finalDelegate = new NoopFinalHandler($response);
                return $finalDelegate->handle($request);
            });
        }

        chdir($cwd);

        $this->request = $psr7Request;

        return new Response(
            strval($psr7Response->getBody()),
            $psr7Response->getStatusCode(),
            $psr7Response->getHeaders()
        );
    }

    private function extractHeaders(Request $request)
    {
        $headers = [];
        $server = $request->getServer();

        $contentHeaders = ['Content-Length' => true, 'Content-Md5' => true, 'Content-Type' => true];
        foreach ($server as $header => $val) {
            $header = implode('-', array_map('ucfirst', explode('-', strtolower(str_replace('_', '-', $header)))));

            if (strpos($header, 'Http-') === 0) {
                $headers[substr($header, 5)] = $val;
            } elseif (isset($contentHeaders[$header])) {
                $headers[$header] = $val;
            }
        }

        return $headers;
    }

    private function convertFiles(array $files)
    {
        $fileObjects = [];
        foreach ($files as $fieldName => $file) {
            if ($file instanceof UploadedFileInterface) {
                $fileObjects[$fieldName] = $file;
            } elseif (!isset($file['tmp_name']) && !isset($file['name'])) {
                $fileObjects[$fieldName] = $this->convertFiles($file);
            } else {
                $fileObjects[$fieldName] = $this->createUploadedFile($file);
            }
        }
        return $fileObjects;
    }

    /**
     * @param string $method
     * @param UriInterface|string $uri
     * @param array $serverParams
     * @return ServerRequestInterface
     */
    private function createRequest(string $method, $uri, array $serverParams)
    {
        $factory = $this->requestFactory;
        if ($factory instanceof ServerRequestFactoryInterface) {
            return $factory->createServerRequest($method, $uri, $serverParams);
        } else {
            return $factory($method, $uri, $serverParams);
        }
    }

    /**
     * @return ResponseInterface
     */
    private function createResponse()
    {
        $factory = $this->responseFactory;
        return $factory instanceof ResponseFactoryInterface ? $factory->createResponse() : $factory();
    }

    /**
     * @param string $uri
     * @return UriInterface
     */
    private function createUri($uri)
    {
        $factory = $this->uriFactory;
        return $factory instanceof UriFactoryInterface ? $factory->createUri($uri) : $factory($uri);
    }

    /**
     * @param resource $inputStream
     * @return StreamInterface
     */
    private function createStream($inputStream)
    {
        $factory = $this->streamFactory;
        return $factory instanceof StreamFactoryInterface ?
            $factory->createStreamFromResource($inputStream) : $factory($inputStream);
    }

    /**
     * @param array $file
     * @return UploadedFileInterface
     */
    private function createUploadedFile($file)
    {

        $factory = $this->uploadedFileFactory;
        return $factory instanceof UploadedFileFactoryInterface ? $factory->createUploadedFile(
            $file['tmp_name'],
            $file['size'],
            $file['error'],
            $file['name'],
            $file['type']
        ) : $factory(
            $file['tmp_name'],
            $file['size'],
            $file['error'],
            $file['name'],
            $file['type']
        );
    }
}
