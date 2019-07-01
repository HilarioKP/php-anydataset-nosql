<?php

namespace ByJG\AnyDataset\NoSql;

use Aws\Result;
use Aws\S3\S3Client;
use ByJG\AnyDataset\Core\GenericIterator;
use ByJG\AnyDataset\Lists\ArrayDataset;
use ByJG\Util\Uri;

class AwsS3Driver implements KeyValueInterface
{

    /**
     * @var S3Client
     */
    protected $s3Client;

    /**
     * @var string
     */
    protected $bucket;

    /**
     * AwsS3Driver constructor.
     *
     *  s3://key:secret@region/bucket
     *
     * @param string $connectionString
     */
    public function __construct($connectionString)
    {
        $uri = new Uri($connectionString);

        $this->s3Client = new S3Client([
            'version'     => 'latest',
            'region'      => $uri->getHost(),
            'credentials' => [
                'key'    => $uri->getUsername(),
                'secret' => $uri->getPassword(),
            ],
        ]);

        $this->bucket = preg_replace('~^/~', '', $uri->getPath());
    }

    /**
     * @param array $options
     * @return GenericIterator
     */
    public function getIterator($options = [])
    {
        $data = array_merge(
            [
                'Bucket' => $this->bucket,
            ],
            $options
        );

        /**
         * @var Result
         */
        $result = $this->s3Client->listObjects($data);

        $contents = [];
        if (isset($result['Contents'])) {
            $contents = $result['Contents'];
        }
        return (new ArrayDataset($contents))->getIterator();
    }

    public function get($key, $options = [])
    {
        $data = array_merge(
            [
                'Bucket' => $this->bucket,
                'Key'    => $key
            ],
            $options
        );

        $result = $this->s3Client->getObject($data);

        return $result["Body"]->getContents();
    }

    public function put($key, $value, $options = [])
    {
        $data = array_merge(
            [
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'Body'   => $value,
            ],
            $options
        );

//        if (!empty($contentType)) {
//            $data['ContentType'] = $contentType;
//        }

        if (!isset($data['ACL'])) {
            $data['ACL'] = 'private';
        }

        return $this->s3Client->putObject($data);
    }

    public function remove($key, $options = [])
    {
        $data = array_merge(
            [
                'Bucket' => $this->bucket,
                'Key'    => $key
            ],
            $options
        );

        $this->s3Client->deleteObject($data);
    }

    public function getDbConnection()
    {
        return $this->s3Client;
    }

    public function getChunk($key, $options = [], $size = 1024, $offset = 0)
    {
        $part = ($offset * $size);

        $untilByte = ($part + $size - 1);

        $options = array_merge(
            $options,
            [
                'Range' => "bytes=${part}-${untilByte}"
            ]
        );

        return $this->get($key, $options);
    }

    /**
     * @param KeyValueDocument[] $keyValueArray
     * @param array $options
     * @return void
     */
    public function putBatch($keyValueArray, $options = [])
    {
        // TODO: Implement putBatch() method.
    }

    /**
     * @param object[] $key
     * @param array $options
     * @return mixed
     */
    public function removeBatch($key, $options = [])
    {
        // TODO: Implement removeBatch() method.
    }
}
