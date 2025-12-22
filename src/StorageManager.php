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

use function dirname;
use function is_array;
use function is_callable;
use function is_string;

class StorageManager
{
    /**
     * @var array<string, Closure>
     */
    protected $aAdapters = [];

    /**
     * @var Config|null
     */
    protected Config|null $xConfig = null;

    /**
     * The constructor
     *
     * @param Closure|null $xConfigGetter
     * @param Translator|null $xTranslator
     */
    public function __construct(private Closure|null $xConfigGetter = null,
        protected Translator|null $xTranslator = null)
    {
        $this->registerDefaults();

        if($xTranslator !== null)
        {
            $this->loadTranslations($xTranslator);
        }
    }

    /**
     * @param Closure $xConfigGetter
     *
     * @return void
     */
    public function setConfigGetter(Closure $xConfigGetter): void
    {
        $this->xConfigGetter = $xConfigGetter;
        $this->xConfig = null;
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
     * @param string $sAdapter
     * @param Closure $cFactory
     *
     * @return void
     */
    public function register(string $sAdapter, Closure $cFactory)
    {
        $this->aAdapters[$sAdapter] = $cFactory;
    }

    /**
     * Register the file storage adapters
     *
     * @return void
     */
    private function registerDefaults(): void
    {
        // Local file system adapter
        $this->register('local', fn(string $sRootDir, array $aOptions) =>
            new LocalFilesystemAdapter($sRootDir, ...$aOptions));
    }

    /**
     * @param string $sAdapter
     * @param string $sRootDir
     * @param array $aOptions
     *
     * @return Filesystem
     * @throws Exception
     */
    public function make(string $sAdapter, string $sRootDir, array $aOptions = []): Filesystem
    {
        if(!isset($this->aAdapters[$sAdapter]) || !is_callable($this->aAdapters[$sAdapter]))
        {
            Logger::error("Jaxon Storage: adapter '$sAdapter' not configured.");
            throw new Exception($this->translator()->trans('errors.storage.adapter'));
        }

        return new Filesystem($this->aAdapters[$sAdapter]($sRootDir, $aOptions));
    }

    /**
     * @throws Exception
     * @return Config
     */
    private function config(): Config
    {
        if($this->xConfig !== null)
        {
            return $this->xConfig;
        }

        if($this->xConfigGetter === null)
        {
            Logger::error("Jaxon Storage: No config getter set.");
            throw new Exception($this->translator()->trans('errors.storage.getter'));
        }

        return $this->xConfig = ($this->xConfigGetter)();
    }

    /**
     * @param string $sOptionName
     *
     * @return Filesystem
     * @throws Exception
     */
    public function get(string $sOptionName): Filesystem
    {
        $xConfig = $this->config();
        $sAdapter = $xConfig->getOption("$sOptionName.adapter");
        $sRootDir = $xConfig->getOption("$sOptionName.dir");
        $aOptions = $xConfig->getOption("$sOptionName.options", []);
        if(!is_string($sAdapter) || !is_string($sRootDir) || !is_array($aOptions))
        {
            Logger::error("Jaxon Storage: incorrect values in '$sOptionName' options.");
            throw new Exception($this->translator()->trans('errors.storage.options'));
        }

        return $this->make($sAdapter, $sRootDir, $aOptions);
    }
}
