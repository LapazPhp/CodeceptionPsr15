<?php
namespace Lapaz\Codeception\GenericMiddleware\Module;


use Codeception\Test\Test;
use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\Factory\ServerRequestFactoryInterface;
use Interop\Http\Factory\StreamFactoryInterface;
use Interop\Http\Factory\UploadedFileFactoryInterface;
use Interop\Http\Factory\UriFactoryInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Lapaz\Codeception\GenericMiddleware\Lib\Connector\MiddlewareClient;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use Psr\Container\ContainerInterface;

class MiddlewareContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerInterface
     */
    public static $container;

    /**
     * @var MiddlewareContainer
     */
    protected $module;

    public function setUp()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('config'));
        file_put_contents(
            vfsStream::url('config/test.php'),
            '<?php return \Lapaz\Codeception\GenericMiddleware\Module\MiddlewareContainerTest::$container;'
        );

        $moduleContainer = $this->createMock(\Codeception\Lib\ModuleContainer::class);
        assert($moduleContainer instanceof \Codeception\Lib\ModuleContainer);

        $this->module = new MiddlewareContainer($moduleContainer);

        $this->module->_pathResolver = function ($path) {
            return vfsStream::url($path);
        };

        $this->module->_setConfig([
            'containerFile' => 'config/test.php',
            'processorName' => 'middleware',
            'requestFactoryName' => 'http.requestFactory',
            'responseFactoryName' => 'http.responseFactory',
            'uriFactoryName' => 'http.uriFactory',
            'streamFactoryName' => 'http.streamFactory',
            'uploadedFileFactoryName' => 'http.uploadedFileFactory',
        ]);
    }

    public function testInitializeWithInterfaces()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnMap([
            ['middleware', $this->createMock(MiddlewareInterface::class)],
            ['http.requestFactory', $this->createMock(ServerRequestFactoryInterface::class)],
            ['http.responseFactory', $this->createMock(ResponseFactoryInterface::class)],
            ['http.uriFactory', $this->createMock(UriFactoryInterface::class)],
            ['http.streamFactory', $this->createMock(StreamFactoryInterface::class)],
            ['http.uploadedFileFactory', $this->createMock(UploadedFileFactoryInterface::class)],
        ]);
        static::$container = $container;

        $this->module->_initialize();
    }

    public function testInitializeWithFunctions()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $nopFunction = function () {};
        $container->method('get')->willReturnMap([
            ['middleware', $nopFunction],
            ['http.requestFactory', $nopFunction],
            ['http.responseFactory', $nopFunction],
            ['http.uriFactory', $nopFunction],
            ['http.streamFactory', $nopFunction],
            ['http.uploadedFileFactory', $nopFunction],
        ]);
        static::$container = $container;

        $this->module->_initialize();
    }

    public function testBeforeRunAfter()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnMap([
            ['middleware', $this->createMock(MiddlewareInterface::class)],
            ['http.requestFactory', $this->createMock(ServerRequestFactoryInterface::class)],
            ['http.responseFactory', $this->createMock(ResponseFactoryInterface::class)],
            ['http.uriFactory', $this->createMock(UriFactoryInterface::class)],
            ['http.streamFactory', $this->createMock(StreamFactoryInterface::class)],
            ['http.uploadedFileFactory', $this->createMock(UploadedFileFactoryInterface::class)],
        ]);
        static::$container = $container;

        $this->module->_initialize();

        $test = $this->createMock('\Codeception\Test\Test');
        assert($test instanceof Test);

        $this->module->_before($test);

        $this->assertInstanceOf(ContainerInterface::class, $this->module->getMiddlewareContainer());
        $this->assertInstanceOf(MiddlewareClient::class, $this->module->client);

        $this->module->_after($test);
    }


}
