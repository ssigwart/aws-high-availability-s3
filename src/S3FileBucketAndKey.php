<?php

namespace ssigwart\AwsHighAvailabilityS3;

/** S3 file bucket and key */
class S3FileBucketAndKey implements S3FileBucketAndKeyProviderInterface
{
	/** @var string AWS region */
	private string $awsRegion;

	/** @var string S3 bucket */
	private string $s3Bucket;

	/** @var string S3 key */
	private string $s3Key;

	/**
	 * Constructor
	 *
	 * @param string $awsRegion AWS region
	 * @param string $s3Bucket S3 bucket
	 * @param string $s3Key S3 key
	 */
	public function __construct(string $awsRegion, string $s3Bucket, string $s3Key)
	{
		$this->awsRegion = $awsRegion;
		$this->s3Bucket = $s3Bucket;
		$this->s3Key = $s3Key;
	}

	/**
	 * Get S3 bucket region
	 *
	 * @return string AWS region
	 */
	public function getS3BucketRegion(): string
	{
		return $this->awsRegion;
	}

	/**
	 * Get S3 bucket
	 *
	 * @return string S3 bucket
	 */
	public function getS3Bucket(): string
	{
		return $this->s3Bucket;
	}

	/**
	 * Get S3 key
	 *
	 * @return string S3 key
	 */
	public function getS3Key(): string
	{
		return $this->s3Key;
	}
}
