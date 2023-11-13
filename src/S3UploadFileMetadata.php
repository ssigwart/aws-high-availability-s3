<?php

namespace ssigwart\AwsHighAvailabilityS3;

/** S3 upload file metadata */
class S3UploadFileMetadata
{
	/** @var string|null Storage class */
	private $storageClass = null;

	/**
	 * Get storage class
	 *
	 * @return string|null Storage class
	 */
	public function getStorageClass(): ?string
	{
		return $this->storageClass;
	}

	/**
	 * Enable standard infrequent access storage class
	 *
	 * @return self
	 */
	public function setStorageClassStandardInfrequentAccess(): self
	{
		$this->storageClass = 'STANDARD_IA';
		return $this;
	}

	/**
	 * Enable one-zone infrequent access access storage class
	 *
	 * @return self
	 */
	public function setStorageClassOneZoneInfrequentAccess(): self
	{
		$this->storageClass = 'ONEZONE_IA';
		return $this;
	}

	/**
	 * Enable reduced redundancy storage class
	 *
	 * @return self
	 */
	public function setStorageClassReducedRedundancy(): self
	{
		$this->storageClass = 'REDUCED_REDUNDANCY';
		return $this;
	}

	/**
	 * Enable intelligent tiering storage class
	 *
	 * @return self
	 */
	public function setStorageClassIntelligentTiering(): self
	{
		$this->storageClass = 'INTELLIGENT_TIERING';
		return $this;
	}

	/**
	 * Enable glacier storage class
	 *
	 * @return self
	 */
	public function setStorageClassGlacier(): self
	{
		$this->storageClass = 'GLACIER';
		return $this;
	}

	/** @var string ACL */
	private $acl = 'private';

	/**
	 * Get ACL
	 *
	 * @return string ACL
	 */
	public function getAcl(): string
	{
		return $this->acl;
	}

	/**
	 * Set private ACL (default)
	 *
	 * @return self
	 */
	public function makePrivate(): self
	{
		$this->acl = 'private';
		return $this;
	}

	/**
	 * Set public-read ACL
	 *
	 * @return self
	 */
	public function makePublic(): self
	{
		$this->acl = 'public-read';
		return $this;
	}

	/** @var string|null Cache control header */
	private $cacheControl = null;

	/**
	 * Get cache control header
	 *
	 * @return string|null Cache control header
	 */
	public function getCacheControl(): ?string
	{
		return $this->cacheControl;
	}

	/**
	 * Set max age for caching
	 *
	 * @param int $sec Max age in seconds
	 *
	 * @return self
	 */
	public function setCachingMaxAge(int $sec): self
	{
		$this->cacheControl = 'max-age=' . $sec;
		return $this;
	}
}
