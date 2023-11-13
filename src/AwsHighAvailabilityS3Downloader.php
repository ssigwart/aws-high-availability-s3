<?php

namespace ssigwart\AwsHighAvailabilityS3;

use Throwable;

/** AWS high availability S3 downloader */
class AwsHighAvailabilityS3Downloader
{
	/** @var \Aws\Sdk AWS SDK */
	private \Aws\Sdk $awsSdk;

	/** @var AwsHighAvailabilityS3DownloaderErrorHandlerInterface|null Error handler interface */
	private ?AwsHighAvailabilityS3DownloaderErrorHandlerInterface $errorHandlerInterface = null;

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
	 * Get error handler interface
	 *
	 * @return AwsHighAvailabilityS3DownloaderErrorHandlerInterface|null Error handler interface
	 */
	public function getErrorHandlerInterface(): ?AwsHighAvailabilityS3DownloaderErrorHandlerInterface
	{
		return $this->errorHandlerInterface;
	}

	/**
	 * Set error handler interface
	 *
	 * @param AwsHighAvailabilityS3DownloaderErrorHandlerInterface|null $errorHandlerInterface Error handler interface
	 *
	 * @return self
	 */
	public function setErrorHandlerInterface(?AwsHighAvailabilityS3DownloaderErrorHandlerInterface $errorHandlerInterface): self
	{
		$this->errorHandlerInterface = $errorHandlerInterface;
		return $this;
	}

	/**
	 * Download file from S3
	 *
	 * @param S3AvailableDownloadFileBucketAndKeyLocations $availableLocations Available locations of the file
	 *
	 * @return string File contents
	 * @throws AwsHighAvailabilityS3DownloaderException
	 */
	public function downloadFileFromS3(S3AvailableDownloadFileBucketAndKeyLocations $availableLocations): string
	{
		// Try available locations
		$firstException = null;
		foreach ($availableLocations->getLocations() as $location)
		{
			try
			{
				// Set location
				$req = [
					'Bucket' => $location->getS3Bucket(),
					'Key' => $location->getS3Key()
				];

				// Make request
				$result = $this->awsSdk->createS3([
					'region' => $location->getS3BucketRegion()
				])->getObject($req);
				return $result->get('Body');
			} catch (Throwable $e) {
				$firstException ??= $e;

				// Call error handler
				if ($this->errorHandlerInterface !== null)
				{
					try {
						$this->errorHandlerInterface->handleDownloadException($location, $e);
					} catch (Throwable $nestedE) {
						throw new AwsHighAvailabilityS3DownloaderException('Failed to download. Exception thrown handling error.', 0, $nestedE);
					}
				}
			}
		}

		// Throw exception
		throw new AwsHighAvailabilityS3DownloaderException('Failed to download. Attempted ' . number_format(count($availableLocations->getLocations())) . ' locations.', 0, $firstException);
	}
}
