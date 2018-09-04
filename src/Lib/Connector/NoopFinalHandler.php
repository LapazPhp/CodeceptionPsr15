<?php
namespace Lapaz\Codeception\Psr15\Lib\Connector;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NoopFinalHandler implements RequestHandlerInterface
{
    /**
     * @var ResponseInterface;
     */
    protected $response;

    /**
     * NoopFinalDelegate constructor.
     *
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->response;
    }
}
