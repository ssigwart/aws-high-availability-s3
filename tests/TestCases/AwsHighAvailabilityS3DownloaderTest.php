<?php

declare(strict_types=1);

use ssigwart\AwsHighAvailabilityS3\AwsHighAvailabilityS3Downloader;
use ssigwart\AwsHighAvailabilityS3\AwsHighAvailabilityS3DownloaderException;
use ssigwart\AwsHighAvailabilityS3\S3AvailableDownloadFileBucketAndKeyLocations;
use ssigwart\AwsHighAvailabilityS3\S3FileBucketAndKey;
use TestAuxFiles\UnitTestAwsHighAvailabilityS3DownloaderErrorHandler;
use TestAuxFiles\UnitTestCase;

/**
 * AWS high availability S3 downloader test
 */
class AwsHighAvailabilityS3DownloaderTest extends UnitTestCase
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
		$locations = new S3AvailableDownloadFileBucketAndKeyLocations($primaryLocation);
		$locations->addAlternativeLocation($backupLocation);

		// Set up mock AWS
		$awsSdk = $this->getMockAwsSdk();
		$s3UsEast1 = $this->getMockS3ClientForDownload([
			[
				'Bucket' => $primaryLocation->getS3Bucket(),
				'Key' => $primaryLocation->getS3Key()
			]
		], self::FILE_CONTENTS, null);
		$this->addExpectedCreateS3Call([
			'region' => $primaryLocation->getS3BucketRegion()
		], $s3UsEast1);
		$this->finalizeMockAwsSdk($awsSdk);

		// Download
		$s3Downloader = new AwsHighAvailabilityS3Downloader($awsSdk);
		$contents = $s3Downloader->downloadFileFromS3($locations);
		self::assertEquals(self::FILE_CONTENTS, $contents);
	}

	/**
	 * Test backup location success
	 */
	public function testBackupLocationSuccess(): void
	{
		// Set up possible locations
		$primaryLocation = new S3FileBucketAndKey('us-east-1', 'phpunit-test-us-east-1', 'us-east-1/path/to/file.txt');
		$backupLocation = new S3FileBucketAndKey('us-west-1', 'phpunit-test-us-west-1', 'us-west-1/path/to/file.txt');
		$locations = new S3AvailableDownloadFileBucketAndKeyLocations($primaryLocation);
		$locations->addAlternativeLocation($backupLocation);

		// Set up mock AWS
		$awsSdk = $this->getMockAwsSdk();
		$s3UsEast1 = $this->getMockS3ClientForDownload([
			[
				'Bucket' => $primaryLocation->getS3Bucket(),
				'Key' => $primaryLocation->getS3Key()
			]
		], null, new \Aws\Exception\CredentialsException());
		$this->addExpectedCreateS3Call([
			'region' => $primaryLocation->getS3BucketRegion()
		], $s3UsEast1);
		$s3UsWest1 = $this->getMockS3ClientForDownload([
			[
				'Bucket' => $backupLocation->getS3Bucket(),
				'Key' => $backupLocation->getS3Key()
			]
		], self::FILE_CONTENTS, null);
		$this->addExpectedCreateS3Call([
			'region' => $backupLocation->getS3BucketRegion()
		], $s3UsWest1);
		$this->finalizeMockAwsSdk($awsSdk);

		// Download
		$s3Downloader = new AwsHighAvailabilityS3Downloader($awsSdk);
		$contents = $s3Downloader->downloadFileFromS3($locations);
		self::assertEquals(self::FILE_CONTENTS, $contents);
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
		$locations = new S3AvailableDownloadFileBucketAndKeyLocations($primaryLocation);
		$locations->addAlternativeLocation($backupLocation);
		$locations->addAlternativeLocation($thirdLocation);

		// Set up mock AWS
		$awsSdk = $this->getMockAwsSdk();
		$s3UsEast1 = $this->getMockS3ClientForDownload([
			[
				'Bucket' => $primaryLocation->getS3Bucket(),
				'Key' => $primaryLocation->getS3Key()
			]
		], null, new \Aws\Exception\CredentialsException());
		$this->addExpectedCreateS3Call([
			'region' => $primaryLocation->getS3BucketRegion()
		], $s3UsEast1);
		$s3UsWest1 = $this->getMockS3ClientForDownload([
			[
				'Bucket' => $backupLocation->getS3Bucket(),
				'Key' => $backupLocation->getS3Key()
			]
		], null, new \Aws\Exception\CredentialsException());
		$this->addExpectedCreateS3Call([
			'region' => $backupLocation->getS3BucketRegion()
		], $s3UsWest1);
		$s3EuCentral1 = $this->getMockS3ClientForDownload([
			[
				'Bucket' => $thirdLocation->getS3Bucket(),
				'Key' => $thirdLocation->getS3Key()
			]
		], self::FILE_CONTENTS, null);
		$this->addExpectedCreateS3Call([
			'region' => $thirdLocation->getS3BucketRegion()
		], $s3EuCentral1);
		$this->finalizeMockAwsSdk($awsSdk);

		// Download
		$s3Downloader = new AwsHighAvailabilityS3Downloader($awsSdk);
		$contents = $s3Downloader->downloadFileFromS3($locations);
		self::assertEquals(self::FILE_CONTENTS, $contents);
	}

	/**
	 * Test both locations failing
	 */
	public function testBothLocationsFailing(): void
	{
		// Set up possible locations
		$primaryLocation = new S3FileBucketAndKey('us-east-1', 'phpunit-test-us-east-1', 'us-east-1/path/to/file.txt');
		$backupLocation = new S3FileBucketAndKey('us-west-1', 'phpunit-test-us-west-1', 'us-west-1/path/to/file.txt');
		$locations = new S3AvailableDownloadFileBucketAndKeyLocations($primaryLocation);
		$locations->addAlternativeLocation($backupLocation);

		// Set up mock AWS
		$awsSdk = $this->getMockAwsSdk();
		$s3UsEast1 = $this->getMockS3ClientForDownload([
			[
				'Bucket' => $primaryLocation->getS3Bucket(),
				'Key' => $primaryLocation->getS3Key()
			]
		], null, new \Aws\Exception\IncalculablePayloadException());
		$this->addExpectedCreateS3Call([
			'region' => $primaryLocation->getS3BucketRegion()
		], $s3UsEast1);
		$s3UsWest1 = $this->getMockS3ClientForDownload([
			[
				'Bucket' => $backupLocation->getS3Bucket(),
				'Key' => $backupLocation->getS3Key()
			]
		], null, new \Aws\Exception\CredentialsException());
		$this->addExpectedCreateS3Call([
			'region' => $backupLocation->getS3BucketRegion()
		], $s3UsWest1);
		$this->finalizeMockAwsSdk($awsSdk);

		// Download
		$s3Downloader = new AwsHighAvailabilityS3Downloader($awsSdk);
		try {
			$contents = $s3Downloader->downloadFileFromS3($locations);
			self::fail('Expected download to fail.');
		} catch (AwsHighAvailabilityS3DownloaderException $e) {
			self::assertEquals('Failed to download. Attempted 2 locations.', $e->getMessage());
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
		$locations = new S3AvailableDownloadFileBucketAndKeyLocations($primaryLocation);
		$locations->addAlternativeLocation($backupLocation);
		$locations->addAlternativeLocation($thirdLocation);

		// Set up mock AWS
		$awsSdk = $this->getMockAwsSdk();
		$s3UsEast1 = $this->getMockS3ClientForDownload([
			[
				'Bucket' => $primaryLocation->getS3Bucket(),
				'Key' => $primaryLocation->getS3Key()
			]
		], null, new \Aws\Exception\CredentialsException());
		$this->addExpectedCreateS3Call([
			'region' => $primaryLocation->getS3BucketRegion()
		], $s3UsEast1);
		$s3UsWest1 = $this->getMockS3ClientForDownload([
			[
				'Bucket' => $backupLocation->getS3Bucket(),
				'Key' => $backupLocation->getS3Key()
			]
		], null, new \Aws\Exception\CredentialsException());
		$this->addExpectedCreateS3Call([
			'region' => $backupLocation->getS3BucketRegion()
		], $s3UsWest1);
		$s3EuCentral1 = $this->getMockS3ClientForDownload([
			[
				'Bucket' => $thirdLocation->getS3Bucket(),
				'Key' => $thirdLocation->getS3Key()
			]
		], null, new \Aws\Exception\CredentialsException());
		$this->addExpectedCreateS3Call([
			'region' => $thirdLocation->getS3BucketRegion()
		], $s3EuCentral1);
		$this->finalizeMockAwsSdk($awsSdk);

		// Download
		$s3Downloader = new AwsHighAvailabilityS3Downloader($awsSdk);
		$s3Downloader->setErrorHandlerInterface(new UnitTestAwsHighAvailabilityS3DownloaderErrorHandler());
		try {
			$contents = $s3Downloader->downloadFileFromS3($locations);
			self::fail('Expected download to fail.');
		} catch (AwsHighAvailabilityS3DownloaderException $e) {
			self::assertEquals('Failed to download. Exception thrown handling error.', $e->getMessage());
			self::assertInstanceOf(RuntimeException::class, $e->getPrevious());
		}
	}
}
