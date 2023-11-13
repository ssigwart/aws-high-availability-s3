<?php

declare(strict_types=1);

namespace TestAuxFiles;

use RuntimeException;
use ssigwart\AwsHighAvailabilityS3\AwsHighAvailabilityS3DownloaderErrorHandlerInterface;
use ssigwart\AwsHighAvailabilityS3\S3FileBucketAndKeyProviderInterface;
use Throwable;

/** Unit test AWS high availability S3 downloader error handler */
class UnitTestAwsHighAvailabilityS3DownloaderErrorHandler implements AwsHighAvailabilityS3DownloaderErrorHandlerInterface
{
	/**
	 * Handle download exception
	 *
	 * @param S3FileBucketAndKeyProviderInterface $location Location
	 * @param Throwable $e Exception
	 */
	public function handleDownloadException(S3FileBucketAndKeyProviderInterface $location, Throwable $e): void
	{
		if ($location->getS3Bucket() === 'phpunit-test-eu-central-1')
			throw new RuntimeException('Europe test.');
	}
}
