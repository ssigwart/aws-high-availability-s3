<?php

namespace ssigwart\AwsHighAvailabilityS3;

use Throwable;

/** AWS high availability S3 download error handler interface */
interface AwsHighAvailabilityS3DownloaderErrorHandlerInterface
{
	/**
	 * Handle download exception
	 *
	 * @param S3FileBucketAndKeyProviderInterface $location Location
	 * @param Throwable $e Exception
	 */
	public function handleDownloadException(S3FileBucketAndKeyProviderInterface $location, Throwable $e): void;
}
