<?php

namespace ssigwart\AwsHighAvailabilityS3;

/** S3 file bucket and key provider interface */
interface S3FileBucketAndKeyProviderInterface
{
	/**
	 * Get S3 bucket region
	 *
	 * @return string AWS region
	 */
	public function getS3BucketRegion(): string;

	/**
	 * Get S3 bucket
	 *
	 * @return string S3 bucket
	 */
	public function getS3Bucket(): string;

	/**
	 * Get S3 key
	 *
	 * @return string S3 key
	 */
	public function getS3Key(): string;
}
