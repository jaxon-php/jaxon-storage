[![Build Status](https://github.com/jaxon-php/jaxon-storage/actions/workflows/test.yml/badge.svg?branch=main)](https://github.com/jaxon-php/jaxon-storage/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jaxon-php/jaxon-storage/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/jaxon-php/jaxon-storage/?branch=main)
[![StyleCI](https://styleci.io/repos/491279814/shield?branch=main)](https://styleci.io/repos/491279814)
[![codecov](https://codecov.io/gh/jaxon-php/jaxon-storage/branch/main/graph/badge.svg?token=8KD4KLQTXO)](https://codecov.io/gh/jaxon-php/jaxon-storage)

[![Latest Stable Version](https://poser.pugx.org/jaxon-php/jaxon-storage/v/stable)](https://packagist.org/packages/jaxon-php/jaxon-storage)
[![Total Downloads](https://poser.pugx.org/jaxon-php/jaxon-storage/downloads)](https://packagist.org/packages/jaxon-php/jaxon-storage)
[![Latest Unstable Version](https://poser.pugx.org/jaxon-php/jaxon-storage/v/unstable)](https://packagist.org/packages/jaxon-php/jaxon-storage)
[![License](https://poser.pugx.org/jaxon-php/jaxon-storage/license)](https://packagist.org/packages/jaxon-php/jaxon-storage)

File storage for the Jaxon library
=================================

This package provides a tiny wrapper for file storage for the Jaxon library using the [PHP League Flysystem](https://flysystem.thephpleague.com) library.

## Features

The library features are provided in the `Jaxon\Storage\StorageManager` class, which implements three functions.

#### Register an adapter

This function registers an adapter from the [Flysystem](https://flysystem.thephpleague.com) library.

```php
    /**
     * @param string $sAdapter
     * @param Closure $cFactory
     *
     * @return void
     */
    public function register(string $sAdapter, Closure $cFactory)
```

The first parameter is the adapter id, and the second is a closure which takes a root dir and an optional array of options as parameters, and returns a `League\Flysystem\FilesystemAdapter` object configured for file input and output at a given location.

By default, the library registers an adapter for the local filesystem.

```php
use League\Flysystem\Local\LocalFilesystemAdapter;

// Local file system adapter
$manager->register('local', function(string $sRootDir, $xOptions) {
    return empty($xOptions) ? new LocalFilesystemAdapter($sRootDir) :
        new LocalFilesystemAdapter($sRootDir, $xOptions);
});
```

#### Create a file input/output object

This function creates a [Flysystem](https://flysystem.thephpleague.com) object for file input and output.

```php
use League\Flysystem\Filesystem;

    /**
     * @param string $sAdapter
     * @param string $sRootDir
     * @param array $aOptions
     *
     * @return Filesystem
     * @throws RequestException
     */
    public function make(string $sAdapter, string $sRootDir, array $aOptions = []): Filesystem
```

The first parameter is the id of a registered adapter, and the other will be passed to the corresponding closure.

The code snippet below writes the given content in the `/var/www/storage/uploads/uploaded-file.txt` file.

```php
$storage = $manager->make('local', '/var/www/storage/uploads');
$storage->write('uploaded-file.txt', $$uploadedContent)
```

#### Create a file input/output object from the Jaxon config

This function creates a [Flysystem](https://flysystem.thephpleague.com) object for file input and output, with options from the  `app.storage.$sOptionName.adapter`, `app.storage.$sOptionName.dir` and `app.storage.$sOptionName.options` entries of the Jaxon library config.

```php
use League\Flysystem\Filesystem;

    /**
     * @param string $sOptionName
     *
     * @return Filesystem
     * @throws RequestException
     */
    public function get(string $sOptionName): Filesystem
```

With this config,

```php
return [
    'app' => [
        'storage' => [
            'uploads' => [
                'adapter' => 'local',
                'dir' => '/var/www/storage/uploads',
                // 'options' => [], // Optional
            ],
        ],
    ],
];
```

The code snippet below writes the given content in the `/var/www/storage/uploads/uploaded-file.txt` file, as in the previous example.

```php
$storage = $manager->get('uploads');
$storage->write('uploaded-file.txt', $$uploadedContent)
```

## Register additional adapters

The [Flysystem](https://flysystem.thephpleague.com) library provides adapters for many other filesystems, which can be registered with this library.

They are provided in separate packages, which need to be installed first.

#### AWS S3 file system adapter

```php
$manager->registerAdapter('aws-s3', function(string $sRootDir, array $aOptions) {
    /** @var \Aws\S3\S3ClientInterface $client */
    $client = new \Aws\S3\S3Client($aOptions['client'] ?? []);
    return new \League\Flysystem\AwsS3V3\AwsS3V3Adapter($client, $aOptions['bucket'] ?? '', $sRootDir);
});
```

#### Async AWS S3 file system adapter

```php
$manager->registerAdapter('async-aws-s3', function(string $sRootDir, array $aOptions) {
    $client = isset($aOptions['client']) ? new \AsyncAws\S3\S3Client($aOptions['client']) : new \AsyncAws\S3\S3Client();
    return new \League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter($client, $aOptions['bucket'] ?? '', $sRootDir);
});
```

#### Google Cloud file system adapter

```php
$manager->registerAdapter('google-cloud', function(string $sRootDir, array $aOptions) {
    $storageClient = new \Google\Cloud\Storage\StorageClient($aOptions['client'] ?? []);
    $bucket = $storageClient->bucket($aOptions['bucket'] ?? '');
    return new \League\Flysystem\AzureBlobStorage\GoogleCloudStorageAdapter($bucket, $sRootDir);
});
```

#### Microsoft Azure file system adapter

```php
$manager->registerAdapter('azure-blob', function(string $sRootDir, array $aOptions) {
    $client = \MicrosoftAzure\Storage\Blob\BlobRestProxy::createBlobService($aOptions['dsn']);
    return new \League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter($client, $aOptions['container'], $sRootDir);
});
```

#### FTP file system adapter

```php
$manager->registerAdapter('ftp', function(string $sRootDir, array $aOptions) {
    $aOptions['root'] = $sRootDir;
    $xOptions = \League\Flysystem\Ftp\FtpConnectionOptions::fromArray($aOptions);
    return new \League\Flysystem\Ftp\FtpAdapter($xOptions);
});
```

#### SFTP V2 file system adapter

```php
$manager->registerAdapter('sftp-v2', function(string $sRootDir, array $aOptions) {
    $provider = new \League\Flysystem\PhpseclibV2\SftpConnectionProvider(...$aOptions);
    return new \League\Flysystem\PhpseclibV2\SftpAdapter($provider, $sRootDir);
});
```

#### SFTP V3 file system adapter

```php
$manager->registerAdapter('sftp-v3', function(string $sRootDir, array $aOptions) {
    $provider = new \League\Flysystem\PhpseclibV3\SftpConnectionProvider(...$aOptions);
    return new \League\Flysystem\PhpseclibV3\SftpAdapter($provider, $sRootDir);
});
```
