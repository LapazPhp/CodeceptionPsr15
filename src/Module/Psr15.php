<?php
namespace Lapaz\Codeception\Psr15\Module;

use Codeception\Configuration;
use Codeception\Exception\ModuleConfigException;
use Codeception\Lib\Framework;
use Codeception\TestInterface;
use Http\Factory\Diactoros\ResponseFactory;
use Http\Factory\Diactoros\ServerRequestFactory;
use Http\Factory\Diactoros\StreamFactory;
use Http\Factory\Diactoros\UploadedFileFactory;
use Http\Factory\Diactoros\UriFactory;
use Lapaz\Codeception\Psr15\Lib\Connector\Psr15Client;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Generic PSR-7,15,17 application functional test support.
 *
 * Example `functional.suite.yml`
 *
 * ```
 * class_name: FunctionalTester
 *   modules:
 *     enabled:
 *       - \Lapaz\Codeception\Psr15\Module\Psr15:
 *           requestHandler: tests/_app/handler.php
 * ```
 *
 * requestHandler:
 * The script which returns an instance which implements PSR-15 RequestHandlerInterface.
 */
class Psr15 extends Framework
{
    /**
     * @inheritDoc
     */
    protected $config = [
        'requestHandler' => '',
    ];

    /**
     * @inheritDoc
     */
    protected $requiredFields = [
        'requestHandler',
    ];

    /**
     * @inheritDoc
     */
    public $client;

    /**
     * @var string
     */
    private $requestHandlerAbsolutePath;

    /**
     * @inheritDoc
     */
    public function _initialize()
    {
        $projectPath = rtrim(Configuration::projectDir(), '/');
        $requestHandlerFile = $this->config['requestHandler'];
        $this->requestHandlerAbsolutePath = $projectPath . '/' . ltrim($requestHandlerFile, '/');

        if (!is_file($this->requestHandlerAbsolutePath)) {
            throw new ModuleConfigException(
                __CLASS__,
                "The requestHandler file does not exist: " . $requestHandlerFile
            );
        }

        /** @noinspection PhpIncludeInspection */
        $requestHandler = require $this->requestHandlerAbsolutePath;

        if (!($requestHandler instanceof RequestHandlerInterface)) {
            throw new ModuleConfigException(
                __CLASS__,
                "PSR-15 incompatible object was returned from: " . $requestHandlerFile
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function _before(TestInterface $test)
    {
        /** @noinspection PhpIncludeInspection */
        $requestHandler = require $this->requestHandlerAbsolutePath;

        $this->client = new Psr15Client();
        $this->client->setFactories(
            new ServerRequestFactory(),
            new ResponseFactory(),
            new UriFactory(),
            new StreamFactory(),
            new UploadedFileFactory()
        );

        $this->client->setRequestHandler($requestHandler);
    }

    /**
     * @inheritDoc
     */
    public function _after(TestInterface $test)
    {
        //Close the session, if any are open
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        parent::_after($test);
    }
}
