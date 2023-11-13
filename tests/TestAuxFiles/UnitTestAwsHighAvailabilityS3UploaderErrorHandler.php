<?php

declare(strict_types=1);

namespace TestAuxFiles;

use RuntimeException;
use ssigwart\AwsHighAvailabilityS3\AwsHighAvailabilityS3UploaderErrorHandlerInterface;
use ssigwart\AwsHighAvailabilityS3\S3FileBucketAndKeyProviderInterface;
use Throwable;

/** Unit test AWS high availability S3 uploader error handler */
class UnitTestAwsHighAvailabilityS3UploaderErrorHandler implements AwsHighAvailabilityS3UploaderErrorHandlerInterface
{
	/**
	 * Handle upload exception
	 *
	 * @param S3FileBucketAndKeyProviderInterface $location Location
	 * @param Throwable $e Exception
	 */
	public function handleUploadException(S3FileBucketAndKeyProviderInterface $location, Throwable $e): void
	{
		if ($location->getS3Bucket() === 'phpunit-test-eu-central-1')
			throw new RuntimeException('Europe test.');
	}
}
