<?php

declare(strict_types=1);

namespace Service\GCS;

use DateTimeInterface;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\Connection\Rest;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter as FlysystemGoogleCloudAdapter;
use RuntimeException;

/**
 * Ignore missing interface methods. They are due to a @mixin annotation in FilesystemAdapter.
 */
class Adapter extends FilesystemAdapter
{
    protected StorageClient $client;

    /**
     * @param FilesystemOperator $driver
     * @param FlysystemGoogleCloudAdapter $adapter
     * @param array $config
     * @param StorageClient $client
     */
    public function __construct(FilesystemOperator $driver, FlysystemGoogleCloudAdapter $adapter, array $config, StorageClient $client)
    {
        parent::__construct($driver, $adapter, $config);

        $this->client = $client;
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param string $path
     * @return string
     *
     * @throws RuntimeException
     */
    public function url($path): string
    {
        $apiEndpoint = rtrim(Rest::DEFAULT_API_ENDPOINT, '/') . '/' . ltrim(Arr::get($this->config, 'bucket'), '/');

        if (isset($this->config['apiEndpoint'])) {
            $apiEndpoint = $this->config['apiEndpoint'];
        }

        return $this->concatPathToUrl($apiEndpoint, $this->prefixer->prefixPath($path));
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * @param string $path
     * @param DateTimeInterface $expiration
     * @param array $options
     * @return string
     */
    public function temporaryUrl($path, $expiration, array $options = []): string
    {
        return $this->getBucket()->object($this->prefixer->prefixPath($path))->signedUrl($expiration, $options);
    }

    /**
     * @return Bucket
     */
    private function getBucket(): Bucket
    {
        return $this->client->bucket(Arr::get($this->config, 'bucket'));
    }
}
