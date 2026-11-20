<?php

/**
 * StorageManager.php
 *
 * File storage manager.
 *
 * @package jaxon-storage
 * @author Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @copyright 2025 Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/jaxon-php/jaxon-core
 */

namespace Jaxon\Storage;

use Jaxon\Config\Config;
use Jaxon\Utils\Translation\Translator;
use Lagdo\Facades\Logger;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Closure;

use function count;
use function dirname;
use function is_a;
use function is_array;
use function is_string;

class StorageManager
{
    /**
     * @var array<string, Closure>
     */
    protected $aFactories = [];

    /**
     * @var array<string, array<string, array|string>>
     */
    protected $aAdapters = [];

    /**
     * @var array
     */
    private array $aCurrentAdapter;

    /**
     * @var Config|null
     */
    protected Config|null $xConfig = null;

    /**
     * @var Closure|null
     */
    protected Closure|null $xConfigGetter = null;

    /**
     * The constructor
     *
     * @param Config|Closure|null $xConfig
     * @param Translator|null $xTranslator
     */
    public function __construct(Config|Closure|null $xConfig = null,
        protected Translator|null $xTranslator = null)
    {
        // Local file system adapter
        $this->registerDriver('local', fn(string $sRootDir, array $aOptions) =>
            new LocalFilesystemAdapter($sRootDir, ...$aOptions));
        $this->registerAdapter('local', 'local');

        $this->resetCurrentAdapter();

        if($xConfig !== null)
        {
            $this->setConfig($xConfig);
        }
        if($xTranslator !== null)
        {
            $this->loadTranslations($xTranslator);
        }
    }

    /**
     * @param string $sAdapter
     * @param array $aDriverOptions
     *
     * @return void
     */
    private function setCurrentAdapter(string $sAdapter, array $aDriverOptions): void
    {
        $aAdapter = $this->aAdapters[$sAdapter];
        $this->aCurrentAdapter = [
            ...$aAdapter,
            'options' => [
                ...($aAdapter['options'] ?? []),
                ...$aDriverOptions,
            ],
        ];
    }

    /**
     * @return void
     */
    private function resetCurrentAdapter(): void
    {
        $this->setCurrentAdapter('local', []);
    }

    /**
     * @param Config|Closure $xConfig
     *
     * @return self
     */
    public function setConfig(Config|Closure $xConfig): self
    {
        $this->xConfig = null;
        $this->xConfigGetter = null;
        is_a($xConfig, Config::class) ?
            $this->xConfig = $xConfig :
            $this->xConfigGetter = $xConfig;

        return $this;
    }

    /**
     * @return void
     */
    private function loadTranslations(Translator $xTranslator): void
    {
        // Translation directory
        $sTranslationDir = dirname(__DIR__) . '/translations';
        // Load the storage translations
        $xTranslator->loadTranslations("$sTranslationDir/en/storage.php", 'en');
        $xTranslator->loadTranslations("$sTranslationDir/fr/storage.php", 'fr');
        $xTranslator->loadTranslations("$sTranslationDir/es/storage.php", 'es');
    }

    /**
     * Get a translator with the translations loaded.
     *
     * @return Translator
     */
    public function translator(): Translator
    {
        if($this->xTranslator !== null)
        {
            return $this->xTranslator;
        }

        $this->xTranslator = new Translator();
        $this->loadTranslations($this->xTranslator);

        return $this->xTranslator;
    }

    /**
     * @param string $sDriver
     * @param Closure $xFactory
     *
     * @return void
     */
    public function registerDriver(string $sDriver, Closure $xFactory): void
    {
        $this->aFactories[$sDriver] ??= $xFactory;
    }

    /**
     * @param string $sAdapter
     * @param string $sDriver
     * @param array $aDriverOptions
     *
     * @return void
     */
    public function registerAdapter(string $sAdapter,
        string $sDriver, array $aDriverOptions = []): void
    {
        if(isset($this->aAdapters[$sAdapter]))
        {
            return;
        }

        if(!isset($this->aFactories[$sDriver]))
        {
            Logger::error("Jaxon Storage: no driver '{$sDriver}' registered.");
            throw new StorageException($this->translator()->trans('errors.storage.driver'));
        }

        $this->aAdapters[$sAdapter] = [
            'factory' => $this->aFactories[$sDriver],
            'options' => $aDriverOptions,
        ];
    }

    /**
     * Register a driver, and an adapter with the same name.
     * This method is provided for compatibility with previous versions.
     *
     * @param string $sDriver
     * @param Closure|string $xFactory
     *
     * @return void
     */
    public function register(string $sDriver, Closure|string $xFactory): void
    {
        if(!is_string($xFactory))
        {
            $this->registerDriver($sDriver, $xFactory);
        }
        $this->registerAdapter($sDriver, $sDriver);
    }

    /**
     * @param string $sAdapter
     * @param array $aDriverOptions
     *
     * @return self
     */
    public function adapter(string $sAdapter, array $aDriverOptions = []): self
    {
        if(!isset($this->aAdapters[$sAdapter]))
        {
            Logger::error("Jaxon Storage: adapter '$sAdapter' not registered.");
            throw new StorageException($this->translator()
                ->trans('errors.storage.adapter'));
        }

        $this->setCurrentAdapter($sAdapter, $aDriverOptions);
        return $this;
    }

    /**
     * @param string $sRootDir
     * @param array $aOptions
     *
     * @return Filesystem
     * @throws StorageException
     */
    public function dir(string $sRootDir, array $aOptions = []): Filesystem
    {
        $sAdapter = $this->aCurrentAdapter;
        $this->resetCurrentAdapter();

        // Make the adapter.
        $xAdapter = $sAdapter['factory']($sRootDir, $sAdapter['options']);
        return new Filesystem($xAdapter, ...$aOptions);
    }

    /**
     * @param string $sRootDir
     * @param array $aOptions
     *
     * @return Filesystem
     * @throws StorageException
     */
    public function make(string $sRootDir, array $aOptions = []): Filesystem
    {
        return $this->dir($sRootDir, $aOptions);
    }

    /**
     * @throws StorageException
     * @return Config
     */
    private function config(): Config
    {
        if($this->xConfig !== null)
        {
            return $this->xConfig;
        }
        if($this->xConfigGetter !== null)
        {
            return $this->xConfig = ($this->xConfigGetter)();
        }

        Logger::error("Jaxon Storage: No config getter set.");
        throw new StorageException($this->translator()
            ->trans('errors.storage.getter'));
    }

    /**
     * @param string $sAdapter
     *
     * @return void
     */
    private function configAdapter(string $sAdapter): void
    {
        if(!isset($this->aAdapters[$sAdapter]))
        {
            $xConfig = $this->config();
            $aAdapter = $xConfig->getOption("adapters.$sAdapter");
            $sDriver = $aAdapter['driver'] ?? null;
            $aDriverOptions = $aAdapter['options'] ?? [];
            if(!is_array($aAdapter) || count($aAdapter) !== 2 ||
                !is_string($sDriver) || !is_array($aDriverOptions))
            {
                Logger::error("Jaxon Storage: incorrect values for adapter.", [
                    'adapter' => $sAdapter,
                    'options' => $aAdapter,
                ]);
                throw new StorageException($this->translator()
                    ->trans('errors.storage.options'));
            }

            $this->registerAdapter($sAdapter, $sDriver, $aDriverOptions);
        }
    }

    /**
     * @param string $sOptionName
     *
     * @return Filesystem
     * @throws StorageException
     */
    public function get(string $sOptionName): Filesystem
    {
        $xConfig = $this->config();
        $sAdapter = $xConfig->getOption("stores.$sOptionName.adapter");
        $sRootDir = $xConfig->getOption("stores.$sOptionName.dir");
        $aOptions = $xConfig->getOption("stores.$sOptionName.options", []);
        if(!is_string($sAdapter) || !is_string($sRootDir) || !is_array($aOptions))
        {
            Logger::error("Jaxon Storage: incorrect values for store.", [
                'store' => $sOptionName,
                'options' => $xConfig->getOption("stores.$sOptionName"),
            ]);
            throw new StorageException($this->translator()
                ->trans('errors.storage.options'));
        }

        $this->configAdapter($sAdapter);

        return $this->adapter($sAdapter)->make($sRootDir, $aOptions);
    }
}
