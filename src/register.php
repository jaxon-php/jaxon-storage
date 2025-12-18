<?php

namespace Jaxon\Storage;

use Jaxon\Config\Config;
use Jaxon\Config\ConfigSetter;
use Jaxon\Utils\Translation\Translator;
use Lagdo\Facades\ContainerWrapper;

use function function_exists;
use function php_sapi_name;

/**
 * Register the values into the Jaxon container
 *
 * @return void
 */
function _register(): void
{
    $di = jaxon()->di();

    // Setup the logger facade.
    ContainerWrapper::setContainer($di);

    // File storage
    if(!$di->h(StorageManager::class))
    {
        $di->set(StorageManager::class, function() use($di): StorageManager {
            $xConfigGetter = function() use($di): Config {
                $aConfigOptions = $di->config()->getAppOption('storage', []);
                return (new ConfigSetter())->newConfig($aConfigOptions);
            };

            return new StorageManager($xConfigGetter, $di->g(Translator::class));
        });
    }
}

function register()
{
    // Do nothing if running in cli.
    if(php_sapi_name() !== 'cli' && function_exists('jaxon'))
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
    if(function_exists('jaxon'))
    {
        return jaxon()->di()->g(StorageManager::class);
    }

    static $xStorageManager = null;
    return $xStorageManager ?: new StorageManager();
}

register();
