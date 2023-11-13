<?php

declare(strict_types=1);

use ssigwart\AwsHighAvailabilityS3\AwsHighAvailabilityS3Uploader;
use ssigwart\AwsHighAvailabilityS3\AwsHighAvailabilityS3UploaderException;
use ssigwart\AwsHighAvailabilityS3\S3AvailableUploadFileBucketAndKeyLocations;
use ssigwart\AwsHighAvailabilityS3\S3FileBucketAndKey;
use ssigwart\AwsHighAvailabilityS3\S3UploadFileMetadata;
use TestAuxFiles\UnitTestAwsHighAvailabilityS3UploaderErrorHandler;
use TestAuxFiles\UnitTestCase;

/**
 * AWS high availability S3 uploader test
 */
class AwsHighAvailabilityS3UploaderTest extends UnitTestCase
{
	/** @var string File contents */
	private const FILE_CONTENTS = 'abc 123';

	/** @var string File content type */
	private const FILE_CONTENT_TYPE = 'text/plain';

	/**
	 * Test primary location success
	 */
	public function testPrimaryLocationSuccess(): void
	{
		// Set up possible locations
		$primaryLocation = new S3FileBucketAndKey('us-east-1', 'phpunit-test-us-east-1', 'us-east-1/path/to/file.txt');
		$backupLocation = new S3FileBucketAndKey('us-west-1', 'phpunit-test-us-west-1', 'us-west-1/path/to/file.txt');
		$locations = new S3AvailableUploadFileBucketAndKeyLocations($primaryLocation);
		$locations->addAlternativeLocation($backupLocation);

		// Set up meta data
		$metadata = new S3UploadFileMetadata();
		$metadata->setStorageClassStandardInfrequentAccess();

		// Set up mock AWS
		$awsSdk = $this->getMockAwsSdk();
		$s3UsEast1 = $this->getMockS3ClientForUpload([
			[
				'ACL' => 'private',
				'Body' => self::FILE_CONTENTS,
				'ContentType' => self::FILE_CONTENT_TYPE,
				'StorageClass' => 'STANDARD_IA',
				'Bucket' => $primaryLocation->getS3Bucket(),
				'Key' => $primaryLocation->getS3Key()
			]
		], null);
		$this->addExpectedCreateS3Call([
			'region' => $primaryLocation->getS3BucketRegion()
		], $s3UsEast1);
		$this->finalizeMockAwsSdk($awsSdk);

		// Upload
		$s3Uploader = new AwsHighAvailabilityS3Uploader($awsSdk);
		$finalLocation = $s3Uploader->uploadFileToS3($locations, self::FILE_CONTENTS, self::FILE_CONTENT_TYPE, $metadata);

		// Should be in primary location
		self::assertEquals($primaryLocation, $finalLocation);
	}

	/**
	 * Test backup location success
	 */
	public function testBackupLocationSuccess(): void
	{
		// Set up possible locations
		$primaryLocation = new S3FileBucketAndKey('us-east-1', 'phpunit-test-us-east-1', 'us-east-1/path/to/file.txt');
		$backupLocation = new S3FileBucketAndKey('us-west-1', 'phpunit-test-us-west-1', 'us-west-1/path/to/file.txt');
		$locations = new S3AvailableUploadFileBucketAndKeyLocations($primaryLocation);
		$locations->addAlternativeLocation($backupLocation);

		// Set up meta data
		$metadata = new S3UploadFileMetadata();
		$metadata->setStorageClassStandardInfrequentAccess();

		// Set up mock AWS
		$awsSdk = $this->getMockAwsSdk();
		$s3UsEast1 = $this->getMockS3ClientForUpload([
			[
				'ACL' => 'private',
				'Body' => self::FILE_CONTENTS,
				'ContentType' => self::FILE_CONTENT_TYPE,
				'StorageClass' => 'STANDARD_IA',
				'Bucket' => $primaryLocation->getS3Bucket(),
				'Key' => $primaryLocation->getS3Key()
			]
		], new \Aws\Exception\CredentialsException());
		$this->addExpectedCreateS3Call([
			'region' => $primaryLocation->getS3BucketRegion()
		], $s3UsEast1);
		$s3UsWest1 = $this->getMockS3ClientForUpload([
			[
				'ACL' => 'private',
				'Body' => self::FILE_CONTENTS,
				'ContentType' => self::FILE_CONTENT_TYPE,
				'StorageClass' => 'STANDARD_IA',
				'Bucket' => $backupLocation->getS3Bucket(),
				'Key' => $backupLocation->getS3Key()
			]
		], null);
		$this->addExpectedCreateS3Call([
			'region' => $backupLocation->getS3BucketRegion()
		], $s3UsWest1);
		$this->finalizeMockAwsSdk($awsSdk);

		// Upload
		$s3Uploader = new AwsHighAvailabilityS3Uploader($awsSdk);
		$finalLocation = $s3Uploader->uploadFileToS3($locations, self::FILE_CONTENTS, self::FILE_CONTENT_TYPE, $metadata);

		// Should be in backup location
		self::assertEquals($backupLocation, $finalLocation);
	}

	/**
	 * Test third location success
	 */
	public function testThirdLocationSuccess(): void
	{
		// Set up possible locations
		$primaryLocation = new S3FileBucketAndKey('us-east-1', 'phpunit-test-us-east-1', 'us-east-1/path/to/file.txt');
		$backupLocation = new S3FileBucketAndKey('us-west-1', 'phpunit-test-us-west-1', 'us-west-1/path/to/file.txt');
		$thirdLocation = new S3FileBucketAndKey('eu-central-1', 'phpunit-test-eu-central-1', 'eu-central-1/path/to/file.txt');
		$locations = new S3AvailableUploadFileBucketAndKeyLocations($primaryLocation);
		$locations->addAlternativeLocation($backupLocation);
		$locations->addAlternativeLocation($thirdLocation);

		// Set up meta data
		$metadata = new S3UploadFileMetadata();
		$metadata->setStorageClassStandardInfrequentAccess();
		$metadata->makePublic();

		// Set up mock AWS
		$awsSdk = $this->getMockAwsSdk();
		$s3UsEast1 = $this->getMockS3ClientForUpload([
			[
				'ACL' => 'public-read',
				'Body' => self::FILE_CONTENTS,
				'ContentType' => self::FILE_CONTENT_TYPE,
				'StorageClass' => 'STANDARD_IA',
				'Bucket' => $primaryLocation->getS3Bucket(),
				'Key' => $primaryLocation->getS3Key()
			]
		], new \Aws\Exception\CredentialsException());
		$this->addExpectedCreateS3Call([
			'region' => $primaryLocation->getS3BucketRegion()
		], $s3UsEast1);
		$s3UsWest1 = $this->getMockS3ClientForUpload([
			[
				'ACL' => 'public-read',
				'Body' => self::FILE_CONTENTS,
				'ContentType' => self::FILE_CONTENT_TYPE,
				'StorageClass' => 'STANDARD_IA',
				'Bucket' => $backupLocation->getS3Bucket(),
				'Key' => $backupLocation->getS3Key()
			]
		], new \Aws\Exception\CredentialsException());
		$this->addExpectedCreateS3Call([
			'region' => $backupLocation->getS3BucketRegion()
		], $s3UsWest1);
		$s3EuCentral1 = $this->getMockS3ClientForUpload([
			[
				'ACL' => 'public-read',
				'Body' => self::FILE_CONTENTS,
				'ContentType' => self::FILE_CONTENT_TYPE,
				'StorageClass' => 'STANDARD_IA',
				'Bucket' => $thirdLocation->getS3Bucket(),
				'Key' => $thirdLocation->getS3Key()
			]
		], null);
		$this->addExpectedCreateS3Call([
			'region' => $thirdLocation->getS3BucketRegion()
		], $s3EuCentral1);
		$this->finalizeMockAwsSdk($awsSdk);

		// Upload
		$s3Uploader = new AwsHighAvailabilityS3Uploader($awsSdk);
		$finalLocation = $s3Uploader->uploadFileToS3($locations, self::FILE_CONTENTS, self::FILE_CONTENT_TYPE, $metadata);

		// Should be in backup location
		self::assertEquals($thirdLocation, $finalLocation);
	}

	/**
	 * Test both locations failing
	 */
	public function testBothLocationsFailing(): void
	{
		// Set up possible locations
		$primaryLocation = new S3FileBucketAndKey('us-east-1', 'phpunit-test-us-east-1', 'us-east-1/path/to/file.txt');
		$backupLocation = new S3FileBucketAndKey('us-west-1', 'phpunit-test-us-west-1', 'us-west-1/path/to/file.txt');
		$locations = new S3AvailableUploadFileBucketAndKeyLocations($primaryLocation);
		$locations->addAlternativeLocation($backupLocation);

		// Set up meta data
		$metadata = new S3UploadFileMetadata();
		$metadata->setStorageClassStandardInfrequentAccess();

		// Set up mock AWS
		$awsSdk = $this->getMockAwsSdk();
		$s3UsEast1 = $this->getMockS3ClientForUpload([
			[
				'ACL' => 'private',
				'Body' => self::FILE_CONTENTS,
				'ContentType' => self::FILE_CONTENT_TYPE,
				'StorageClass' => 'STANDARD_IA',
				'Bucket' => $primaryLocation->getS3Bucket(),
				'Key' => $primaryLocation->getS3Key()
			]
		], new \Aws\Exception\IncalculablePayloadException());
		$this->addExpectedCreateS3Call([
			'region' => $primaryLocation->getS3BucketRegion()
		], $s3UsEast1);
		$s3UsWest1 = $this->getMockS3ClientForUpload([
			[
				'ACL' => 'private',
				'Body' => self::FILE_CONTENTS,
				'ContentType' => self::FILE_CONTENT_TYPE,
				'StorageClass' => 'STANDARD_IA',
				'Bucket' => $backupLocation->getS3Bucket(),
				'Key' => $backupLocation->getS3Key()
			]
		], new \Aws\Exception\CredentialsException());
		$this->addExpectedCreateS3Call([
			'region' => $backupLocation->getS3BucketRegion()
		], $s3UsWest1);
		$this->finalizeMockAwsSdk($awsSdk);

		// Upload
		$s3Uploader = new AwsHighAvailabilityS3Uploader($awsSdk);
		try {
			$s3Uploader->uploadFileToS3($locations, self::FILE_CONTENTS, self::FILE_CONTENT_TYPE, $metadata);
			self::fail('Expected upload to fail.');
		} catch (AwsHighAvailabilityS3UploaderException $e) {
			self::assertEquals('Failed to upload. Attempted 2 locations.', $e->getMessage());
			self::assertInstanceOf(\Aws\Exception\IncalculablePayloadException::class, $e->getPrevious());
		}
	}

	/**
	 * Test exception handler exception
	 */
	public function testExceptionHandlerException(): void
	{
		// Set up possible locations
		$primaryLocation = new S3FileBucketAndKey('us-east-1', 'phpunit-test-us-east-1', 'us-east-1/path/to/file.txt');
		$backupLocation = new S3FileBucketAndKey('us-west-1', 'phpunit-test-us-west-1', 'us-west-1/path/to/file.txt');
		$thirdLocation = new S3FileBucketAndKey('eu-central-1', 'phpunit-test-eu-central-1', 'eu-central-1/path/to/file.txt');
		$locations = new S3AvailableUploadFileBucketAndKeyLocations($primaryLocation);
		$locations->addAlternativeLocation($backupLocation);
		$locations->addAlternativeLocation($thirdLocation);

		// Set up meta data
		$metadata = new S3UploadFileMetadata();
		$metadata->setStorageClassStandardInfrequentAccess();

		// Set up mock AWS
		$awsSdk = $this->getMockAwsSdk();
		$s3UsEast1 = $this->getMockS3ClientForUpload([
			[
				'ACL' => 'private',
				'Body' => self::FILE_CONTENTS,
				'ContentType' => self::FILE_CONTENT_TYPE,
				'StorageClass' => 'STANDARD_IA',
				'Bucket' => $primaryLocation->getS3Bucket(),
				'Key' => $primaryLocation->getS3Key()
			]
		], new \Aws\Exception\CredentialsException());
		$this->addExpectedCreateS3Call([
			'region' => $primaryLocation->getS3BucketRegion()
		], $s3UsEast1);
		$s3UsWest1 = $this->getMockS3ClientForUpload([
			[
				'ACL' => 'private',
				'Body' => self::FILE_CONTENTS,
				'ContentType' => self::FILE_CONTENT_TYPE,
				'StorageClass' => 'STANDARD_IA',
				'Bucket' => $backupLocation->getS3Bucket(),
				'Key' => $backupLocation->getS3Key()
			]
		], new \Aws\Exception\CredentialsException());
		$this->addExpectedCreateS3Call([
			'region' => $backupLocation->getS3BucketRegion()
		], $s3UsWest1);
		$s3EuCentral1 = $this->getMockS3ClientForUpload([
			[
				'ACL' => 'private',
				'Body' => self::FILE_CONTENTS,
				'ContentType' => self::FILE_CONTENT_TYPE,
				'StorageClass' => 'STANDARD_IA',
				'Bucket' => $thirdLocation->getS3Bucket(),
				'Key' => $thirdLocation->getS3Key()
			]
		], new \Aws\Exception\CredentialsException());
		$this->addExpectedCreateS3Call([
			'region' => $thirdLocation->getS3BucketRegion()
		], $s3EuCentral1);
		$this->finalizeMockAwsSdk($awsSdk);

		// Upload
		$s3Uploader = new AwsHighAvailabilityS3Uploader($awsSdk);
		$s3Uploader->setErrorHandlerInterface(new UnitTestAwsHighAvailabilityS3UploaderErrorHandler());
		try {
			$s3Uploader->uploadFileToS3($locations, self::FILE_CONTENTS, self::FILE_CONTENT_TYPE, $metadata);
			self::fail('Expected upload to fail.');
		} catch (AwsHighAvailabilityS3UploaderException $e) {
			self::assertEquals('Failed to upload. Exception thrown handling error.', $e->getMessage());
			self::assertInstanceOf(RuntimeException::class, $e->getPrevious());
		}
	}
}
