<?php

namespace League\Flysystem\AwsS3v3;

use Aws\Result;
use Aws\S3\Exception\DeleteMultipleObjectsException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\Exception\S3MultipartUploadException;
use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\CanOverwriteFiles;
use League\Flysystem\Config;
use League\Flysystem\Util;

class AwsS3Adapter extends AbstractAdapter implements CanOverwriteFiles
{
    const PUBLIC_GRANT_URI = 'http://acs.amazonaws.com/groups/global/AllUsers';

    /**
     * @var array
     */
    protected static $resultMap = [
        'Body'          => 'contents',
        'ContentLength' => 'size',
        'ContentType'   => 'mimetype',
        'Size'          => 'size',
        'Metadata'      => 'metadata',
        'StorageClass'  => 'storageclass',
        'ETag'          => 'etag',
        'VersionId'     => 'versionid'
    ];

    /**
     * @var array
     */
    protected static $metaOptions = [
        'ACL',
        'CacheControl',
        'ContentDisposition',
        'ContentEncoding',
        'ContentLength',
        'ContentMD5',
        'ContentType',
        'Expires',
        'GrantFullControl',
        'GrantRead',
        'GrantReadACP',
        'GrantWriteACP',
        'Metadata',
        'RequestPayer',
        'SSECustomerAlgorithm',
        'SSECustomerKey',
        'SSECustomerKeyMD5',
        'SSEKMSKeyId',
        'ServerSideEncryption',
        'StorageClass',
        'Tagging',
        'WebsiteRedirectLocation',
    ];

    /**
     * @var S3ClientInterface
     */
    protected $s3Client;

    /**
     * @var string
     */
    protected $bucket;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var bool
     */
    private $streamReads;

    public function __construct(S3ClientInterface $client, $bucket, $prefix = '', array $options = [], $streamReads = true)
    {
        $this->s3Client = $client;
        $this->bucket = $bucket;
        $this->setPathPrefix($prefix);
        $this->options = $options;
        $this->streamReads = $streamReads;
    }

    /**
     * Get the S3Client bucket.
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Set the S3Client bucket.
     *
     * @return string
     */
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;
    }

    /**
     * Get the S3Client instance.
     *
     * @return S3ClientInterface
     */
    public function getClient()
    {
        return $this->s3Client;
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return false|array false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, $config);
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return false|array false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, $config);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath)
    {
        if ( ! $this->copy($path, $newpath)) {
            return false;
        }

        return $this->delete($path);
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        $location = $this->applyPathPrefix($path);

        $command = $this->s3Client->getCommand(
            'deleteObject',
            [
                'Bucket' => $this->bucket,
                'Key'    => $location,
            ]
        );

        $this->s3Client->execute($command);

        return ! $this->has($path);
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        try {
            $prefix = $this->applyPathPrefix($dirname) . '/';
            $this->s3Client->deleteMatchingObjects($this->bucket, $prefix);
        } catch (DeleteMultipleObjectsException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return bool|array
     */
    public function createDir($dirname, Config $config)
    {
        return $this->upload($dirname . '/', '', $config);
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function has($path)
    {
        $location = $this->applyPathPrefix($path);

        if ($this->s3Client->doesObjectExist($this->bucket, $location, $this->options)) {
            return true;
        }

        return $this->doesDirectoryExist($location);
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return false|array
     */
    public function read($path)
    {
        $response = $this->readObject($path);

        if ($response !== false) {
            $response['contents'] = $response['contents']->getContents();
        }

        return $response;
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        $prefix = $this->applyPathPrefix(rtrim($directory, '/') . '/');
        $options = ['Bucket' => $this->bucket, 'Prefix' => ltrim($prefix, '/')];

        if ($recursive === false) {
            $options['Delimiter'] = '/';
        }

        $listing = $this->retrievePaginatedListing($options);
        $normalizer = [$this, 'normalizeResponse'];
        $normalized = array_map($normalizer, $listing);

        return Util::emulateDirectories($normalized);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function retrievePaginatedListing(array $options)
    {
        $resultPaginator = $this->s3Client->getPaginator('ListObjectsV2', $options);
        $listing = [];

        foreach ($resultPaginator as $result) {
            $listing = array_merge($listing, $result->get('Contents') ?: [], $result->get('CommonPrefixes') ?: []);
        }

        return $listing;
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return false|array
     */
    public function getMetadata($path)
    {
        $command = $this->s3Client->getCommand(
            'headObject',
            [
                'Bucket' => $this->bucket,
                'Key'    => $this->applyPathPrefix($path),
            ] + $this->options
        );

        /* @var Result $result */
        try {
            $result = $this->s3Client->execute($command);
        } catch (S3Exception $exception) {
            if ($this->is404Exception($exception)) {
                return false;
            }

            throw $exception;
        }

        return $this->normalizeResponse($result->toArray(), $path);
    }

    /**
     * @return bool
     */
    private function is404Exception(S3Exception $exception)
    {
        $response = $exception->getResponse();

        if ($response !== null && $response->getStatusCode() === 404) {
            return true;
        }

        return false;
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return false|array
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return false|array
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return false|array
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Write a new file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->upload($path, $resource, $config);
    }

    /**
     * Update a file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->upload($path, $resource, $config);
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {
        try {
            $this->s3Client->copy(
                $this->bucket,
                $this->applyPathPrefix($path),
                $this->bucket,
                $this->applyPathPrefix($newpath),
                $this->getRawVisibility($path) === AdapterInterface::VISIBILITY_PUBLIC
                    ? 'public-read' : 'private',
                $this->options
            );
        } catch (S3Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream($path)
    {
        $response = $this->readObject($path);

        if ($response !== false) {
            $response['stream'] = $response['contents']->detach();
            unset($response['contents']);
        }

        return $response;
    }

    /**
     * Read an object and normalize the response.
     *
     * @param string $path
     *
     * @return array|bool
     */
    protected function readObject($path)
    {
        $options = [
            'Bucket' => $this->bucket,
            'Key'    => $this->applyPathPrefix($path),
        ] + $this->options;

        if ($this->streamReads && ! isset($options['@http']['stream'])) {
            $options['@http']['stream'] = true;
        }

        $command = $this->s3Client->getCommand('getObject', $options + $this->options);

        try {
            /** @var Result $response */
            $response = $this->s3Client->execute($command);
        } catch (S3Exception $e) {
            return false;
        }

        return $this->normalizeResponse($response->toArray(), $path);
    }

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {
        $command = $this->s3Client->getCommand(
            'putObjectAcl',
            [
                'Bucket' => $this->bucket,
                'Key'    => $this->applyPathPrefix($path),
                'ACL'    => $visibility === AdapterInterface::VISIBILITY_PUBLIC ? 'public-read' : 'private',
            ]
        );

        try {
            $this->s3Client->execute($command);
        } catch (S3Exception $exception) {
            return false;
        }

        return compact('path', 'visibility');
    }

    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getVisibility($path)
    {
        return ['visibility' => $this->getRawVisibility($path)];
    }

    /**
     * {@inheritdoc}
     */
    public function applyPathPrefix($path)
    {
        return ltrim(parent::applyPathPrefix($path), '/');
    }

    /**
     * {@inheritdoc}
     */
    public function setPathPrefix($prefix)
    {
        $prefix = ltrim((string) $prefix, '/');

        return parent::setPathPrefix($prefix);
    }

    /**
     * Get the object acl presented as a visibility.
     *
     * @param string $path
     *
     * @return string
     */
    protected function getRawVisibility($path)
    {
        $command = $this->s3Client->getCommand(
            'getObjectAcl',
            [
                'Bucket' => $this->bucket,
                'Key'    => $this->applyPathPrefix($path),
            ]
        );

        $result = $this->s3Client->execute($command);
        $visibility = AdapterInterface::VISIBILITY_PRIVATE;

        foreach ($result->get('Grants') as $grant) {
            if (
                isset($grant['Grantee']['URI'])
                && $grant['Grantee']['URI'] === self::PUBLIC_GRANT_URI
                && $grant['Permission'] === 'READ'
            ) {
                $visibility = AdapterInterface::VISIBILITY_PUBLIC;
                break;
            }
        }

        return $visibility;
    }

    /**
     * Upload an object.
     *
     * @param string          $path
     * @param string|resource $body
     * @param Config          $config
     *
     * @return array|bool
     */
    protected function upload($path, $body, Config $config)
    {
        $key = $this->applyPathPrefix($path);
        $options = $this->getOptionsFromConfig($config);
        $acl = array_key_exists('ACL', $options) ? $options['ACL'] : 'private';

        if (!$this->isOnlyDir($path)) {
            if ( ! isset($options['ContentType'])) {
                $options['ContentType'] = Util::guessMimeType($path, $body);
            }

            if ( ! isset($options['ContentLength'])) {
                $options['ContentLength'] = is_resource($body) ? Util::getStreamSize($body) : Util::contentSize($body);
            }

            if ($options['ContentLength'] === null) {
                unset($options['ContentLength']);
            }
        }

        try {
            $this->s3Client->upload($this->bucket, $key, $body, $acl, ['params' => $options]);
        } catch (S3MultipartUploadException $multipartUploadException) {
            return false;
        }

        return $this->normalizeResponse($options, $path);
    }

    /**
     * Check if the path contains only directories
     *
     * @param string $path
     *
     * @return bool
     */
    private function isOnlyDir($path)
    {
        return substr($path, -1) === '/';
    }

    /**
     * Get options from the config.
     *
     * @param Config $config
     *
     * @return array
     */
    protected function getOptionsFromConfig(Config $config)
    {
        $options = $this->options;

        if ($visibility = $config->get('visibility')) {
            // For local reference
            $options['visibility'] = $visibility;
            // For external reference
            $options['ACL'] = $visibility === AdapterInterface::VISIBILITY_PUBLIC ? 'public-read' : 'private';
        }

        if ($mimetype = $config->get('mimetype')) {
            // For local reference
            $options['mimetype'] = $mimetype;
            // For external reference
            $options['ContentType'] = $mimetype;
        }

        foreach (static::$metaOptions as $option) {
            if ( ! $config->has($option)) {
                continue;
            }
            $options[$option] = $config->get($option);
        }

        return $options;
    }

    /**
     * Normalize the object result array.
     *
     * @param array  $response
     * @param string $path
     *
     * @return array
     */
    protected function normalizeResponse(array $response, $path = null)
    {
        $result = [
            'path' => $path ?: $this->removePathPrefix(
                isset($response['Key']) ? $response['Key'] : $response['Prefix']
            ),
        ];
        $result = array_merge($result, Util::pathinfo($result['path']));

        if (isset($response['LastModified'])) {
            $result['timestamp'] = strtotime($response['LastModified']);
        }

        if ($this->isOnlyDir($result['path'])) {
            $result['type'] = 'dir';
            $result['path'] = rtrim($result['path'], '/');

            return $result;
        }

        return array_merge($result, Util::map($response, static::$resultMap), ['type' => 'file']);
    }

    /**
     * @param string $location
     *
     * @return bool
     */
    protected function doesDirectoryExist($location)
    {
        // Maybe this isn't an actual key, but a prefix.
        // Do a prefix listing of objects to determine.
        $command = $this->s3Client->getCommand(
            'ListObjectsV2',
            [
                'Bucket'  => $this->bucket,
                'Prefix'  => rtrim($location, '/') . '/',
                'MaxKeys' => 1,
            ]
        );

        try {
            $result = $this->s3Client->execute($command);

            return $result['Contents'] || $result['CommonPrefixes'];
        } catch (S3Exception $e) {
            if (in_array($e->getStatusCode(), [403, 404], true)) {
                return false;
            }

            throw $e;
        }
    }
}
