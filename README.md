[![Build Status](https://github.com/jaxon-php/jaxon-storage/actions/workflows/test.yml/badge.svg?branch=main)](https://github.com/jaxon-php/jaxon-storage/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jaxon-php/jaxon-storage/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/jaxon-php/jaxon-storage/?branch=main)
[![StyleCI](https://styleci.io/repos/1109084794/shield?branch=main)](https://styleci.io/repos/1109084794)
[![codecov](https://codecov.io/gh/jaxon-php/jaxon-storage/graph/badge.svg?token=OFyT9WzVxt)](https://codecov.io/gh/jaxon-php/jaxon-storage)

[![Latest Stable Version](https://poser.pugx.org/jaxon-php/jaxon-storage/v/stable)](https://packagist.org/packages/jaxon-php/jaxon-storage)
[![Total Downloads](https://poser.pugx.org/jaxon-php/jaxon-storage/downloads)](https://packagist.org/packages/jaxon-php/jaxon-storage)
[![Latest Unstable Version](https://poser.pugx.org/jaxon-php/jaxon-storage/v/unstable)](https://packagist.org/packages/jaxon-php/jaxon-storage)
[![License](https://poser.pugx.org/jaxon-php/jaxon-storage/license)](https://packagist.org/packages/jaxon-php/jaxon-storage)

File storage for the Jaxon library
=================================

This package provides a tiny wrapper for file storage for the Jaxon library using the [PHP League Flysystem](https://flysystem.thephpleague.com) library.

## Features

The library features are provided in the `Jaxon\Storage\StorageManager` class.

Starting from version 1.1.0, a global function is available to get the instance of the storage manager.

```php
use function Jaxon\Storage\storage;
```

It creates and returns a static instance of the `Jaxon\Storage\StorageManager` class.

#### Register an adapter

The first step is to register a [Flysystem](https://flysystem.thephpleague.com) adapter as a driver.

```php
    /**
     * @param string $sDriver
     * @param Closure $xFactory
     *
     * @return void
     */
    public function registerDriver(string $sDriver, Closure $xFactory): void
```

The first parameter is the driver id, and the second is a closure which takes a root dir and an array of options as parameters, and returns a `League\Flysystem\FilesystemAdapter` object for file input and output at the given location.

An adapter then needs to be registered, by specifying a driver and the corresponding options.

```php
    /**
     * @param string $sAdapter
     * @param string $sDriver
     * @param array $aDriverOptions
     *
     * @return void
     */
    public function registerAdapter(string $sAdapter,
        string $sDriver, array $aDriverOptions = []): void
```

By default, the library registers an adapter for the local filesystem.

```php
use League\Flysystem\Local\LocalFilesystemAdapter;
use function Jaxon\Storage\storage;

// Local file system adapter
storage()->registerDriver('local', function(string $sRootDir, array $aOptions) {
    return new LocalFilesystemAdapter($sRootDir, ...$aOptions);
});
storage()->registerAdapter('local', 'local');
```

> Note: Starting from version 2.0, the use of aliases is removed.

For compatibility with the 1.2 and prior releases, this function registers a driver and an adapter with the same name.

```php
    /**
     * @param string $sAdapter
     * @param Closure $xFactory
     *
     * @return void
     */
    public function register(string $sAdapter, Closure $xFactory): void
```

#### Create a file input/output object

A [Flysystem](https://flysystem.thephpleague.com) object for file input and output is created using the `adapter()` and `dir()` functions.

```php
use function Jaxon\Storage\storage;

$storage = storage()
    ->adapter('local')
    ->dir('/var/www/storage/uploads');
$storage->write('uploaded-file.txt', $uploadedContent)
```

The `adapter()` function takes the id of a registered adapter as parameter, while the `dir()` function takes the path to the root dir.

Without the call to `adapter()`, the `local` adapter is used by default.

The code snippet below writes the given content in the `/var/www/storage/uploads/uploaded-file.txt` file.

The adapter and directory options can be passed to the `adapter()` and `dir()` functions.
The adapter options are merged with those defined in the call to `registerAdapter()`, set on the adapter object (e.g `LocalFilesystemAdapter`), while the directory options are set on the returned `Filesystem` object. Their values are then described in their respective documentations.

```php
use function Jaxon\Storage\storage;

$aAdapterOptions = [
    'lazyRootCreation' => true,
];
$aDirOptions = [
    'config' => [
        'public_url' => '/uploads',
    ],
];
$storage = storage()
    ->adapter('local', $aAdapterOptions)
    ->dir('/var/www/storage/uploads', $aDirOptions);
$storage->write('uploaded-file.txt', $uploadedContent)
```

#### Create a file input/output object from a Jaxon Config object

The [Flysystem](https://flysystem.thephpleague.com) object for file input and output can also be created from option values in a `Jaxon\Config\Config` object.
For the storage with id `uploads`, the `adapter`, `dir` and `options` values will be read in the `storage.stores.uploads` entry.

```php
use Jaxon\Config\ConfigSetter;
use function Jaxon\Storage\storage;

// Set the storage config
$setter = new ConfigSetter();
$config = $setter->newConfig([
    'storage' => [
        'adapters' => [
            // Adapters options
            'local' => [
                'driver' => 'local', // Mandatory
                'options' => [], // Optional
            ],
        ]
        'stores' => [
            'uploads' => [
                'adapter' => 'local', // Mandatory
                'dir' => '/var/www/storage/uploads',
                // 'options' => [], // Optional
            ],
        ],
    ],
]);
storage()->setConfig($config);

// Write a file
$storage = storage()->get('uploads');
$storage->write('uploaded-file.txt', $uploadedContent)
```

The code snippet above writes the given content in the `/var/www/storage/uploads/uploaded-file.txt` file, as in the previous example.

```php
use function Jaxon\Storage\storage;

$storage = storage()->get('uploads');
$storage->write('uploaded-file.txt', $uploadedContent)
```

Multiple adapters can use the same driver, but with different options.

```php
$config = $setter->newConfig([
    'storage' => [
        'adapters' => [
            // Adapters options
            'uploads' => [
                'driver' => 'local',
                'options' => [], // Local adapter options for the uploads
            ],
            'exports' => [
                'driver' => 'local',
                'options' => [], // Local adapter options for the exports
            ],
        ],
        'stores' => [],
    ],
]);
```

## Using without the Jaxon library

Starting from version 1.1.0, the Jaxon Storage classes do not depend on the [jaxon-core](https://github.com/jaxon-php/jaxon-core) classes anymore.
As a consequence, some features will not be automatically available, and will need an extra setup.

#### The locale for translations

Without the [jaxon-core](https://github.com/jaxon-php/jaxon-core) library, the Jaxon Storage will create its own instance of the `Jaxon\Utils\Translation\Translator` class, which will then need to be set.

```php
use function Jaxon\Storage\storage;

// Set the translation locle to french.
storage()->translator()->setLocale('fr');
```

#### The storage config options

A call to the `storage()->get()` function will by default throw an exception due to missing config options.
The library then needs to be provided with a `Jaxon\Config\Config` object populated with the config options, or a closure which returns one, using the `setConfig()` method.

## Register additional drivers

By default, an adapter is always registered for the local filesystem.
The [Flysystem](https://flysystem.thephpleague.com) library provides adapters for many other filesystems, which need to be registered before they can be used.

They are provided in separate packages, which need to be installed first.

#### AWS S3 file system adapter

https://flysystem.thephpleague.com/docs/adapter/aws-s3-v3/

```php
composer require league/flysystem-aws-s3-v3:^3.0
```

```php
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use function Jaxon\Storage\storage;

storage()->registerDriver('aws-s3', function(string $sRootDir, array $aOptions) {
    $client = new S3Client($aOptions['client'] ?? []);
    return new AwsS3V3Adapter($client, $aOptions['bucket'] ?? '', $sRootDir);
});
```

#### Async AWS S3 file system adapter

https://flysystem.thephpleague.com/docs/adapter/async-aws-s3/

```php
composer require league/flysystem-async-aws-s3:^3.0
```

```php
use AsyncAws\S3\S3Client;
use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use function Jaxon\Storage\storage;

storage()->registerDriver('async-aws-s3', function(string $sRootDir, array $aOptions) {
    $client = isset($aOptions['client']) ? new S3Client($aOptions['client']) : new S3Client();
    return new AsyncAwsS3Adapter($client, $aOptions['bucket'] ?? '', $sRootDir);
});
```

#### Google Cloud file system adapter

https://flysystem.thephpleague.com/docs/adapter/google-cloud-storage/

```php
composer require league/flysystem-google-cloud-storage:^3.0
```

```php
use Google\Cloud\Storage\StorageClient;
use League\Flysystem\AzureBlobStorage\GoogleCloudStorageAdapter;
use function Jaxon\Storage\storage;

storage()->registerDriver('google-cloud', function(string $sRootDir, array $aOptions) {
    $storageClient = new StorageClient($aOptions['client'] ?? []);
    $bucket = $storageClient->bucket($aOptions['bucket'] ?? '');
    return new GoogleCloudStorageAdapter($bucket, $sRootDir);
});
```

#### Microsoft Azure file system adapter

https://flysystem.thephpleague.com/docs/adapter/azure-blob-storage/

```php
composer require league/flysystem-azure-blob-storage:^3.0
```

```php
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use function Jaxon\Storage\storage;

storage()->registerDriver('azure-blob', function(string $sRootDir, array $aOptions) {
    $client = BlobRestProxy::createBlobService($aOptions['dsn']);
    return new AzureBlobStorageAdapter($client, $aOptions['container'], $sRootDir);
});
```

#### FTP file system adapter

https://flysystem.thephpleague.com/docs/adapter/ftp/

```php
composer require league/flysystem-ftp:^3.0
```

```php
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use function Jaxon\Storage\storage;

storage()->registerDriver('ftp', function(string $sRootDir, array $aOptions) {
    $aOptions['root'] = $sRootDir;
    $xOptions = FtpConnectionOptions::fromArray($aOptions);
    return new FtpAdapter($xOptions);
});
```

#### SFTP V2 file system adapter

https://flysystem.thephpleague.com/docs/adapter/sftp-v2/

```php
composer require league/flysystem-sftp:^3.0
```

```php
use League\Flysystem\PhpseclibV2\SftpAdapter;
use League\Flysystem\PhpseclibV2\SftpConnectionProvider;
use function Jaxon\Storage\storage;

storage()->registerDriver('sftp-v2', function(string $sRootDir, array $aOptions) {
    $provider = new SftpConnectionProvider(...$aOptions);
    return new SftpAdapter($provider, $sRootDir);
});
```

#### SFTP V3 file system adapter

https://flysystem.thephpleague.com/docs/adapter/sftp-v3/

```php
composer require league/flysystem-sftp-v3:^3.0
```

```php
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use function Jaxon\Storage\storage;

storage()->registerDriver('sftp-v3', function(string $sRootDir, array $aOptions) {
    $provider = new SftpConnectionProvider(...$aOptions);
    return new SftpAdapter($provider, $sRootDir);
});
```
