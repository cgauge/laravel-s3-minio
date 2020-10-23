<?php declare(strict_types=1);

namespace CustomerGauge\Minio\Testing;

use Aws\S3\S3Client;
use Illuminate\Contracts\Config\Repository;
use Throwable;

class Minio
{
    private $endpoint;

    private $key;

    private $secret;

    private $config;

    public function __construct(
        string $host = 'minio',
        int $port = 9000,
        string $key = 'customergauge',
        string $secret = 'phpunit123',
        ?Repository $config = null
    ) {
        $this->endpoint = 'http://' . $host . ':' . $port;
        $this->key = $key;
        $this->secret = $secret;
        $this->config = $config ?? config();
    }

    public function client(): S3Client
    {
        return new S3Client([
            'region' => 'local',
            'version' => '2006-03-01',
            'endpoint' => $this->endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $this->key,
                'secret' => $this->secret,
            ],
        ]);
    }

    public function disk(string $disk, callable $callback)
    {
        $client = $this->client();

        $bucket = "$disk-bucket";

        // Let's go ahead and configure Laravel filesystem with the provided disk
        // so that the code being tested can properly interact with minio.
        $this->config->set("filesystems.disks.$disk", [
            'driver' => 's3',
            'region' => 'local',
            'bucket' => $bucket,
            'endpoint' => $this->endpoint,
            'use_path_style_endpoint' => true,
            'key' => $this->key,
            'secret' => $this->secret,
        ]);

        try {
            // If the bucket already exists, it will throw an exception which we can ignore.
            // If the bucket doesn't exist yet, let's create it so that the code will be
            // able to properly interact with it.
            $client->createBucket(['Bucket' => $bucket]);
        } catch (Throwable $e) {

        }

        try {
            $callback($client, $bucket);
        } finally {
            // Whether the developer's code fails or not, we can make a best effort
            // into cleaning up the bucket and deleting it so thatnext time the
            // test runs, we can successfully create an empty bucket.
            $iterator = $client->getIterator('ListObjects', ['Bucket' => $bucket]);

            foreach ($iterator as $object) {
                $client->deleteObject([
                    'Bucket' => $bucket,
                    'Key' => $object['Key']
                ]);
            }

            $client->deleteBucket(['Bucket' => $bucket]);
        }
    }
}
