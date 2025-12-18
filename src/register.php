<?php

namespace Jaxon\Storage;

use Jaxon\App\Config\ConfigManager;
use Jaxon\Storage\StorageManager;
use Lagdo\Facades\ContainerWrapper;

use function function_exists;
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
            return new StorageManager($c->g(ConfigManager::class));
        });
    }
}

function register()
{
    if(function_exists('jaxon'))
    {
        // Setup the logger facade.
        ContainerWrapper::setContainer(jaxon()->di());
    }

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
