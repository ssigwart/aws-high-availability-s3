<?php

namespace ssigwart\AwsHighAvailabilityS3;

use Throwable;

/** AWS high availability S3 uploader error handler interface */
interface AwsHighAvailabilityS3UploaderErrorHandlerInterface
{
	/**
	 * Handle upload exception
	 *
	 * @param S3FileBucketAndKeyProviderInterface $location Location
	 * @param Throwable $e Exception
	 */
	public function handleUploadException(S3FileBucketAndKeyProviderInterface $location, Throwable $e): void;
}
