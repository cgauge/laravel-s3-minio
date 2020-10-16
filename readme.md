# Laravel S3 Minio 📁

This library provides a convenient way to write test code that runs
against Minio, an S3 compatible storage.

# Installation

```bash
composer require customergauge/minio
```

# Usage

```php
$minio = new Minio();

$minio->disk('my-bucket', function (S3Client $client, string $bucket) {
    $this->post('/my/endpoint/that/interacts/with/s3', [])
        ->assertSuccessful();

    $object = $client->getObject([
        'Bucket' => $bucket,
        'Key' => "/my/expected/s3/key"
    ]);

    $content = $object['Body']->getContents();

    $this->assertStringContainsString('partial-file-content', $content);
});
```

# Docker Example

```yaml
  minio:
    image: minio/minio
    ports:
    - 9000:9000
    environment:
      - MINIO_ACCESS_KEY=customergauge
      - MINIO_SECRET_KEY=phpunit123
    command: server /data
```
