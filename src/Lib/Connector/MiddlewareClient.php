<?php
namespace Lapaz\Codeception\GenericMiddleware\Lib\Connector;

use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\Factory\ServerRequestFactoryInterface;
use Interop\Http\Factory\StreamFactoryInterface;
use Interop\Http\Factory\UploadedFileFactoryInterface;
use Interop\Http\Factory\UriFactoryInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
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

        $uri = $request->getUri();

        $queryParams = array();
        $postParams = array();
        $queryString = parse_url($request->getUri(), PHP_URL_QUERY);
        if ($queryString != '') {
            parse_str($queryString, $queryParams);
        }
        if ($request->getMethod() !== 'GET') {
            $postParams = $request->getParameters();
        }

        $inputStream = fopen('php://memory', 'r+');
        $content = $request->getContent();
        if ($content !== null) {
            fwrite($inputStream, $content);
            rewind($inputStream);
        }

        $psr7Request = $this->createRequest($serverParams)
            ->withMethod($request->getMethod())
            ->withUri($this->createUri($uri))
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
        chdir(codecept_root_dir());

        if ($this->processor instanceof MiddlewareInterface) {
            $finalDelegate = new NoopFinalDelegate($psr7ResponsePrototype);
            $psr7Response = $this->processor->process($psr7Request, $finalDelegate);
        } else {
            $psr7Response = call_user_func($this->processor, $psr7Request, $psr7ResponsePrototype, function ($request, $response) {
                $finalDelegate = new NoopFinalDelegate($response);
                return $finalDelegate->process($request);
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
        $headers = array();
        $server = $request->getServer();

        $contentHeaders = array('Content-Length' => true, 'Content-Md5' => true, 'Content-Type' => true);
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
        $fileObjects = array();
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
     * @param array $serverParams
     * @return ServerRequestInterface
     */
    private function createRequest($serverParams)
    {
        $factory = $this->requestFactory;
        return $factory instanceof ServerRequestFactoryInterface ?
            $factory->createServerRequestFromArray($serverParams) : $factory($serverParams);
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
