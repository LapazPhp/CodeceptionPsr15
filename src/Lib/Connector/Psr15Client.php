<?php
namespace Lapaz\Codeception\Psr15\Lib\Connector;

use Codeception\Configuration;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;

class Psr15Client extends Client
{
    /**
     * @var RequestHandlerInterface
     */
    protected $requestHandler;

    /**
     * @var ServerRequestFactoryInterface
     */
    protected $requestFactory;

    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @var UriFactoryInterface
     */
    protected $uriFactory;

    /**
     * @var StreamFactoryInterface
     */
    protected $streamFactory;

    /**
     * @var UploadedFileFactoryInterface
     */
    protected $uploadedFileFactory;

    /**
     * @param ServerRequestFactoryInterface $requestFactory
     * @param ResponseFactoryInterface $responseFactory
     * @param UriFactoryInterface $uriFactory
     * @param StreamFactoryInterface $streamFactory
     * @param UploadedFileFactoryInterface $uploadedFileFactory
     */
    public function setFactories(
        ServerRequestFactoryInterface $requestFactory,
        ResponseFactoryInterface $responseFactory,
        UriFactoryInterface $uriFactory,
        StreamFactoryInterface $streamFactory,
        UploadedFileFactoryInterface $uploadedFileFactory
    )
    {
        $this->requestFactory = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->uriFactory = $uriFactory;
        $this->streamFactory = $streamFactory;
        $this->uploadedFileFactory = $uploadedFileFactory;
    }

    /**
     * @param RequestHandlerInterface $requestHandler
     */
    public function setRequestHandler(RequestHandlerInterface $requestHandler)
    {
        $this->requestHandler = $requestHandler;
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

        $psr7Request = $this->requestFactory->createServerRequest(
            $method,
            $this->uriFactory->createUri($uri),
            $serverParams
        )
            ->withBody($this->streamFactory->createStreamFromResource($inputStream))
            ->withQueryParams($queryParams)
            ->withParsedBody($postParams)
            ->withCookieParams($request->getCookies())
        ;

        foreach ($this->extractHeaders($request) as $k => $v) {
            $psr7Request = $psr7Request->withHeader($k, $v);
        }

        $uploadedFiles = $request->getFiles();
        if (count($uploadedFiles) > 0) {
            $psr7UploadedFiles = array_map([$this, 'convertFiles'], $uploadedFiles);
            $psr7Request = $psr7Request->withUploadedFiles($psr7UploadedFiles);
        }

        $cwd = getcwd();
        chdir(Configuration::projectDir());

        $psr7Response = $this->requestHandler->handle($psr7Request);

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
                $fileObjects[$fieldName] = $this->uploadedFileFactory->createUploadedFile(
                    $file['tmp_name'],
                    $file['size'],
                    $file['error'],
                    $file['name'],
                    $file['type']
                );
            }
        }
        return $fileObjects;
    }
}
