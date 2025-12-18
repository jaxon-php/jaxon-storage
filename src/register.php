<?php

namespace Jaxon\Storage;

use Jaxon\App\Config\ConfigManager;
use Jaxon\App\I18n\Translator;
use Jaxon\Storage\StorageManager;
use Psr\Log\LoggerInterface;

use function dirname;
use function Jaxon\jaxon;
use function php_sapi_name;

/**
 * Register the values into the container
 *
 * @return void
 */
function _register(): void
{
    $di = jaxon()->di();
    if(!$di->h(StorageManager::class))
    {
        // File storage
        $di->set(StorageManager::class, function($c): StorageManager { 
            // Translation directory
            $sTranslationDir = dirname(__DIR__) . '/translations';
            // Load the storage translations
            $xTranslator = $c->g(Translator::class);
            $xTranslator->loadTranslations("$sTranslationDir/en/storage.php", 'en');
            $xTranslator->loadTranslations("$sTranslationDir/fr/storage.php", 'fr');
            $xTranslator->loadTranslations("$sTranslationDir/es/storage.php", 'es');

            return new StorageManager($c->g(ConfigManager::class),
                $xTranslator, $c->g(LoggerInterface::class));
        });
    }
}

function register()
{
    // Do nothing if running in cli.
    if(php_sapi_name() !== 'cli')
    {
        _register();
    };
}

/**
 * Get the storage manager
 *
 * @return StorageManager
 */
function storage(): StorageManager
{
    return jaxon()->di()->g(StorageManager::class);
}

register();
