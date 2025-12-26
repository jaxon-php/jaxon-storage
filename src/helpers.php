<?php

namespace Jaxon\Storage;

use function function_exists;

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

    static $xStorageManager = new StorageManager();
    return $xStorageManager;
}
