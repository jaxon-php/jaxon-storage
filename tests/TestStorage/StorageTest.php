<?php

namespace Jaxon\Storage\Tests\TestStorage;

use Jaxon\Exception\RequestException;
use Jaxon\Storage\StorageManager;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;

use function Jaxon\jaxon;
use function Jaxon\Storage\_register;
use function file_get_contents;

class StorageTest extends TestCase
{
    /**
     * @var StorageManager
     */
    protected $xManager;

    /**
     * @var string
     */
    protected $sInputDir;

    public function setUp(): void
    {
        _register();

        $this->sInputDir = __DIR__ . '/../files';
        $this->xManager = jaxon()->di()->g(StorageManager::class);
    }

    public function tearDown(): void
    {
        jaxon()->reset();
        parent::tearDown();
    }

    /**
     * @throws RequestException
     */
    public function testStorageReader()
    {
        $xInputStorage = $this->xManager->make('local', $this->sInputDir);
        $sInputContent = $xInputStorage->read('hello.txt');

        $this->assertEquals(file_get_contents("{$this->sInputDir}/hello.txt"), $sInputContent);
    }

    public function testStorageWriter()
    {
        $this->xManager->register('memory', fn() => new InMemoryFilesystemAdapter());
        jaxon()->config()->setAppOptions([
            'adapter' => 'memory',
            'dir' => 'files',
            'options' => [],
        ], 'storage.memory');

        $xInputStorage = $this->xManager->make('local', $this->sInputDir);
        $sInputContent = $xInputStorage->read('hello.txt');

        $xOutputStorage = $this->xManager->get('memory');
        $xOutputStorage->write('hello.txt', $sInputContent);
        $sOutputContent = $xOutputStorage->read('hello.txt');

        $this->assertEquals($sOutputContent, $sInputContent);
    }

    public function testErrorUnknownAdapter()
    {
        $this->expectException(RequestException::class);
        $xUnknownStorage = $this->xManager->make('unknown', $this->sInputDir);
    }

    public function testErrorUnknownConfig()
    {
        $this->expectException(RequestException::class);
        $xUnknownStorage = $this->xManager->get('unknown');
    }

    public function testErrorIncorrectConfigAdapter()
    {
        jaxon()->config()->setAppOptions([
            'adapter' => null,
            'dir' => 'files',
            'options' => [],
        ], 'storage.custom');

        $this->expectException(RequestException::class);
        $xErrorStorage = $this->xManager->get('custom');
    }

    public function testErrorIncorrectConfigDir()
    {
        jaxon()->config()->setAppOptions([
            'adapter' => 'memory',
            'dir' => null,
            'options' => [],
        ], 'storage.custom');

        $this->expectException(RequestException::class);
        $xErrorStorage = $this->xManager->get('custom');
    }

    public function testErrorIncorrectConfigOptions()
    {
        jaxon()->config()->setAppOptions([
            'adapter' => 'memory',
            'dir' => 'files',
            'options' => null,
        ], 'storage.custom');

        $this->expectException(RequestException::class);
        $xErrorStorage = $this->xManager->get('custom');
    }
}
