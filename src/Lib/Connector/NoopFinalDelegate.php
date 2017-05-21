<?php
namespace Lapaz\Codeception\GenericMiddleware\Lib\Connector;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class NoopFinalDelegate implements DelegateInterface
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
    public function process(ServerRequestInterface $request)
    {
        return $this->response;
    }
}
