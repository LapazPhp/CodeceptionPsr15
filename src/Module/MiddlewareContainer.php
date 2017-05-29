<?php
namespace Lapaz\Codeception\GenericMiddleware\Module;

use Codeception\Configuration;
use Codeception\Exception\ModuleConfigException;
use Codeception\Lib\Framework;
use Codeception\TestInterface;
use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\Factory\ServerRequestFactoryInterface;
use Interop\Http\Factory\StreamFactoryInterface;
use Interop\Http\Factory\UploadedFileFactoryInterface;
use Interop\Http\Factory\UriFactoryInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Lapaz\Codeception\GenericMiddleware\Lib\Connector\MiddlewareClient;
use Psr\Container\ContainerInterface;

/**
 * Generic PSR-7 middleware functional test support.
 * It requires a PHP standard container (PSR-11) interface which provides middleware processor and PSR-7 object
 * factories. Both style standard interface (PSR-15 single pass) and raw function (Express double pass) are supported.
 *
 * If you want to use this module with Express style, your container must provide raw function or object implementing
 * `__invoke` method instead of MiddlewareInterface or PSR-17 factories.
 *
 *
 * Example `functional.suite.yml`
 *
 * ```
 * class_name: FunctionalTester
 *   modules:
 *     enabled:
 *       - \Lapaz\Codeception\GenericMiddleware\Module\MiddlewareContainer:
 *         containerFile: tests/_app/container.php
 *         processorName: http.middlewarePipe
 *         requestFactoryName: http.requestFactory
 *         responseFactoryName: http.responseFactory
 *         uriFactoryName: http.uriFactory
 *         streamFactoryName: http.streamFactory
 *         uploadedFileFactoryName: http.uploadedFileFactory
 * ```
 *
 * containerFile:
 * The script which returns PSR-11 container which contains all dependencies else.
 *
 * processorName:
 * ID of MiddlewareInterface implementation or callable function. It takes ServerRequestInterface and returns
 * ResponseInterface. Though single middleware interface required but actually it executes whole pipeline of your
 * application middleware stack.
 *
 * <object>FactoryName:
 * IDs of PSR-17 factory implementation or callable functions.
 */
class MiddlewareContainer extends Framework
{
    /**
     * @inheritDoc
     */
    protected $config = [
        'containerFile' => '',
        'processorName' => '',
        'requestFactoryName' => '',
        'responseFactoryName' => '',
        'uriFactoryName' => '',
        'streamFactoryName' => '',
        'uploadedFileFactoryName' => '',
    ];

    /**
     * @inheritDoc
     */
    protected $requiredFields = [
        'containerFile',
        'processorName',
        'requestFactoryName',
        'responseFactoryName',
        'uriFactoryName',
        'streamFactoryName',
        'uploadedFileFactoryName',
    ];

    /**
     * @inheritDoc
     */
    public $client;

    /**
     * @var string
     */
    public $containerFile;

    /**
     * @var ContainerInterface
     */
    public $container;

    /**
     * @inheritDoc
     */
    public function _initialize()
    {
        $this->containerFile = Configuration::projectDir() . '/' . $this->config['containerFile'];

        if (!is_file($this->containerFile)) {
            throw new ModuleConfigException(
                __CLASS__,
                "The objects file does not exist: " . $this->containerFile
            );
        }

        /** @noinspection PhpIncludeInspection */
        $this->container = require $this->containerFile;

        if (!($this->container instanceof ContainerInterface)) {
            throw new ModuleConfigException(
                __CLASS__,
                "PSR-11 incompatible object was returned from: " . $this->containerFile
            );
        }

        $this->checkService('processorName', MiddlewareInterface::class, true);

        $this->checkService('requestFactoryName', ServerRequestFactoryInterface::class, true);
        $this->checkService('responseFactoryName', ResponseFactoryInterface::class, true);
        $this->checkService('uriFactoryName', UriFactoryInterface::class, true);
        $this->checkService('streamFactoryName', StreamFactoryInterface::class, true);
        $this->checkService('uploadedFileFactoryName', UploadedFileFactoryInterface::class, true);
    }

    /**
     * @inheritDoc
     */
    public function _before(TestInterface $test)
    {
        /** @noinspection PhpIncludeInspection */
        $this->container = require $this->containerFile;

        $this->client = new MiddlewareClient();
        $this->client->setFactories(
            $this->container->get($this->config['requestFactoryName']),
            $this->container->get($this->config['responseFactoryName']),
            $this->container->get($this->config['uriFactoryName']),
            $this->container->get($this->config['streamFactoryName']),
            $this->container->get($this->config['uploadedFileFactoryName'])
        );

        $this->client->setProcessor(
            $this->container->get($this->config['processorName'])
        );
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

    /**
     * @return \Psr\Container\ContainerInterface
     */
    public function getMiddlewareContainer()
    {
        return $this->container;
    }

    private function checkService($itemName, $expectedType, $allowCallback = false)
    {
        $serviceName = $this->config[$itemName];
        if (!$this->container->has($serviceName)) {
            throw new ModuleConfigException(
                __CLASS__,
                $itemName . " is not found: " . $this->containerFile
            );
        }

        $object = $this->container->get($serviceName);

        if ($allowCallback && is_callable($object)) {
            return;
        }

        if (!($object instanceof $expectedType)) {
            throw new ModuleConfigException(
                __CLASS__,
                "The service " . $serviceName . " (" . get_class($object). ") is not compatible with expected type: " . $expectedType
            );
        }
    }
}
