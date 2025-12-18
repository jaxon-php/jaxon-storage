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

use Jaxon\App\Config\ConfigManager;
use Jaxon\Exception\RequestException;
use Jaxon\Utils\Translation\Translator;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Psr\Log\LoggerInterface;
use Closure;

use function dirname;
use function function_exists;
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
     * @var Translator|null
     */
    protected Translator|null $xTranslator = null;

    /**
     * The constructor
     *
     * @param ConfigManager $xConfigManager
     * @param LoggerInterface $logger
     */
    public function __construct(protected ConfigManager $xConfigManager,
        protected LoggerInterface $logger)
    {
        $this->registerDefaults();
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

        $this->xTranslator = !function_exists('jaxon') ? new Translator() :
            jaxon()->di()->g(Translator::class);
        // Translation directory
        $sTranslationDir = dirname(__DIR__) . '/translations';
        // Load the storage translations
        $this->xTranslator->loadTranslations("$sTranslationDir/en/storage.php", 'en');
        $this->xTranslator->loadTranslations("$sTranslationDir/fr/storage.php", 'fr');
        $this->xTranslator->loadTranslations("$sTranslationDir/es/storage.php", 'es');

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
    private function registerDefaults()
    {
        // Local file system adapter
        $this->register('local', function(string $sRootDir, $xOptions) {
            return empty($xOptions) ? new LocalFilesystemAdapter($sRootDir) :
                new LocalFilesystemAdapter($sRootDir, $xOptions);
        });
    }

    /**
     * @param string $sAdapter
     * @param string $sRootDir
     * @param array $aOptions
     *
     * @return Filesystem
     * @throws RequestException
     */
    public function make(string $sAdapter, string $sRootDir, array $aOptions = []): Filesystem
    {
        if(!isset($this->aAdapters[$sAdapter]) || !is_callable($this->aAdapters[$sAdapter]))
        {
            $this->logger->error("Jaxon Storage: adapter '$sAdapter' not configured.");
            throw new RequestException($this->translator()->trans('errors.storage.adapter'));
        }

        return new Filesystem(($this->aAdapters[$sAdapter])($sRootDir, $aOptions));
    }

    /**
     * @param string $sOptionName
     *
     * @return Filesystem
     * @throws RequestException
     */
    public function get(string $sOptionName): Filesystem
    {
        $sConfigKey = "storage.$sOptionName";
        if(!$this->xConfigManager->hasAppOption($sConfigKey))
        {
            $this->logger->error("Jaxon Storage: No '$sConfigKey' in options.");
            throw new RequestException($this->translator()->trans('errors.storage.options'));
        }

        $sAdapter = $this->xConfigManager->getAppOption("$sConfigKey.adapter");
        $sRootDir = $this->xConfigManager->getAppOption("$sConfigKey.dir");
        $aOptions = $this->xConfigManager->getAppOption("$sConfigKey.options", []);
        if(!is_string($sAdapter) || !is_string($sRootDir) || !is_array($aOptions))
        {
            $this->logger->error("Jaxon Storage: incorrect values in '$sConfigKey' options.");
            throw new RequestException($this->translator()->trans('errors.storage.options'));
        }

        return $this->make($sAdapter, $sRootDir, $aOptions);
    }
}
