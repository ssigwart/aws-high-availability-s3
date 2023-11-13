<?php

namespace ssigwart\AwsHighAvailabilityS3;

use Throwable;

/** AWS high availability S3 uploader */
class AwsHighAvailabilityS3Uploader
{
	/** @var \Aws\Sdk AWS SDK */
	private \Aws\Sdk $awsSdk;

	/** @var int Public caching TTL */
	private int $publicCachingTtl = 31536000;

	/** @var AwsHighAvailabilityS3UploaderErrorHandlerInterface|null Error handler interface */
	private ?AwsHighAvailabilityS3UploaderErrorHandlerInterface $errorHandlerInterface = null;

	/**
	 * Constructor
	 *
	 * @param \Aws\Sdk $awsSdk AWS SDK
	 */
	public function __construct(\Aws\Sdk $awsSdk)
	{
		$this->awsSdk = $awsSdk;
	}

	/**
	 * Upload file to S3 (defaults to private unless changed by S3UploadFileMetadata options)
	 *
	 * @param S3AvailableUploadFileBucketAndKeyLocations $availableLocations Available locations
	 * @param string $contents Contents
	 * @param string $contentType Content type
	 * @param S3UploadFileMetadata|null $opts Opts
	 *
	 * @return S3FileBucketAndKeyProviderInterface The location the file was actually stored
	 * @throws AwsHighAvailabilityS3UploaderException
	 */
	public function uploadFileToS3(S3AvailableUploadFileBucketAndKeyLocations $availableLocations, string $contents, string $contentType, ?S3UploadFileMetadata $opts): S3FileBucketAndKeyProviderInterface
	{
		$req = [
			'ACL' => 'private',
			'Body'=> $contents,
			'ContentType' => $contentType
		];
		if ($opts !== null)
		{
			// Set storage class
			if ($opts->getStorageClass() !== null)
				$req['StorageClass'] = $opts->getStorageClass();

			// Set ACL
			$req['ACL'] = $opts->getAcl();

			// Set cache control head
			$cacheControl = $opts->getCacheControl();
			if ($cacheControl !== null)
				$req['CacheControl'] = $cacheControl;
		}

		// Try available locations
		$firstException = null;
		foreach ($availableLocations->getLocations() as $location)
		{
			try
			{
				// Set location
				$req['Bucket'] = $location->getS3Bucket();
				$req['Key'] = $location->getS3Key();

				// Make request
				$this->awsSdk->createS3([
					'region' => $location->getS3BucketRegion()
				])->putObject($req);

				// All good, so return location we stored it at
				return $location;
			} catch (Throwable $e) {
				$firstException ??= $e;

				// Call error handler
				if ($this->errorHandlerInterface !== null)
				{
					try {
						$this->errorHandlerInterface->handleUploadException($location, $e);
					} catch (Throwable $nestedE) {
						throw new AwsHighAvailabilityS3UploaderException('Failed to upload. Exception thrown handling error.', 0, $nestedE);
					}
				}
			}
		}

		// Throw exception
		throw new AwsHighAvailabilityS3UploaderException('Failed to upload. Attempted ' . number_format(count($availableLocations->getLocations())) . ' locations.', 0, $firstException);
	}

	/**
	 * Upload private file to S3
	 *
	 * @param S3AvailableUploadFileBucketAndKeyLocations $availableLocations Available locations
	 * @param string $contents Contents
	 * @param string $contentType Content type
	 * @param S3UploadFileMetadata|null $opts Options
	 *
	 * @return S3FileBucketAndKeyProviderInterface The location the file was actually stored
	 * @throws AwsHighAvailabilityS3UploaderException
	 */
	public function uploadPrivateFileToS3(S3AvailableUploadFileBucketAndKeyLocations $availableLocations, string $contents, string $contentType, ?S3UploadFileMetadata $opts): S3FileBucketAndKeyProviderInterface
	{
		$opts ??= new S3UploadFileMetadata();
		$opts->makePrivate();
		return $this->uploadFileToS3($availableLocations, $contents, $contentType, $opts);
	}

	/**
	 * Upload public file to S3
	 *
	 * @param S3AvailableUploadFileBucketAndKeyLocations $availableLocations Available locations
	 * @param string $contents Contents
	 * @param string $contentType Content type
	 * @param S3UploadFileMetadata|null $opts Options
	 *
	 * @return S3FileBucketAndKeyProviderInterface The location the file was actually stored
	 * @throws AwsHighAvailabilityS3UploaderException
	 */
	public function uploadPubliclyCachedFileToS3(S3AvailableUploadFileBucketAndKeyLocations $availableLocations, string $contents, string $contentType, ?S3UploadFileMetadata $opts): S3FileBucketAndKeyProviderInterface
	{
		$opts ??= new S3UploadFileMetadata();
		$opts->makePublic();
		$opts->setCachingMaxAge($this->publicCachingTtl);
		return $this->uploadFileToS3($availableLocations, $contents, $contentType, $opts);
	}

	/**
	 * Get public caching TTL
	 *
	 * @return int Public caching TTL
	 */
	public function getPublicCachingTtl(): int
	{
		return $this->publicCachingTtl;
	}

	/**
	 * Set public caching TTL
	 *
	 * @param int $publicCachingTtl Public caching TTL
	 *
	 * @return self
	 */
	public function setPublicCachingTtl(int $publicCachingTtl): self
	{
		$this->publicCachingTtl = $publicCachingTtl;
		return $this;
	}

	/**
	 * Get error handler interface
	 *
	 * @return AwsHighAvailabilityS3UploaderErrorHandlerInterface|null Error handler interface
	 */
	public function getErrorHandlerInterface(): ?AwsHighAvailabilityS3UploaderErrorHandlerInterface
	{
		return $this->errorHandlerInterface;
	}

	/**
	 * Set error handler interface
	 *
	 * @param AwsHighAvailabilityS3UploaderErrorHandlerInterface|null $errorHandlerInterface Error handler interface
	 *
	 * @return self
	 */
	public function setErrorHandlerInterface(?AwsHighAvailabilityS3UploaderErrorHandlerInterface $errorHandlerInterface): self
	{
		$this->errorHandlerInterface = $errorHandlerInterface;
		return $this;
	}
}
