<?php

namespace ssigwart\AwsHighAvailabilityS3;

/** S3 available download file bucket and key locations */
class S3AvailableDownloadFileBucketAndKeyLocations
{
	/** @var S3FileBucketAndKeyProviderInterface[] Locations */
	private array $locations = [];

	/**
	 * Construct
	 *
	 * @param S3FileBucketAndKeyProviderInterface $preferredFileLocation Preferred file location
	 */
	public function __construct(S3FileBucketAndKeyProviderInterface $preferredFileLocation)
	{
		$this->locations[] = $preferredFileLocation;
	}

	/**
	 * Get locations
	 *
	 * @return S3FileBucketAndKeyProviderInterface[] Locations
	 */
	public function getLocations(): array
	{
		return $this->locations;
	}

	/**
	 * Add alternative location
	 *
	 * @param S3FileBucketAndKeyProviderInterface $location Location
	 *
	 * @return self
	 */
	public function addAlternativeLocation(S3FileBucketAndKeyProviderInterface $location): self
	{
		$this->locations[] = $location;
		return $this;
	}
}
