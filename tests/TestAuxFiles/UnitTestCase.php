<?php

declare(strict_types=1);

namespace TestAuxFiles;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Throwable;

/** Unit test case */
class UnitTestCase extends TestCase
{
	/** @var array[] Expected createS3 call parameters */
	private array $expectedCreateS3CallParams = [];

	/** @var \Aws\S3\S3Client|MockObject[] Expected createS3 return values */
	private array $expectedCreateS3Returns = [];

	/**
	 * Get mock AWS SDK
	 *
	 * @return \Aws\Sdk|MockObject Mock AWS SDK
	 */
	protected function getMockAwsSdk(): \Aws\Sdk|MockObject
	{
		$this->expectedCreateS3CallParams;
		$mockBuilder = $this->getMockBuilder(\Aws\Sdk::class);
		$mockBuilder->disableOriginalConstructor();
		$mockBuilder->disableAutoReturnValueGeneration();
		$mock = $mockBuilder->getMock();

		return $mock;
	}

	/**
	 * Add expected createS3 call
	 *
	 * @param array $expectedParams Expected parameters
	 * @param \Aws\S3\S3Client|MockObject $rtn Rtn
	 */
	protected function addExpectedCreateS3Call(array $expectedParams, \Aws\S3\S3Client|MockObject $rtn): void
	{
		$this->expectedCreateS3CallParams[] = $expectedParams;
		$this->expectedCreateS3Returns[] = $rtn;
	}

	/**
	 * Finalize mock AWS SDK
	 *
	 * @param \Aws\Sdk|MockObject $mockAwsSdk Mock AWS SDK
	 */
	protected function finalizeMockAwsSdk(\Aws\Sdk|MockObject $mockAwsSdk): void
	{
		$mockAwsSdk->expects(self::exactly(count($this->expectedCreateS3CallParams)))->method('__call')->with('createS3', self::callback(function($args) {
			$expectedParams = array_shift($this->expectedCreateS3CallParams);
			self::assertEquals([$expectedParams], $args);
			return true;
		}))->willReturnCallback(function() {
			return array_shift($this->expectedCreateS3Returns);
		});
	}

	/**
	 * Get mock S3 client for upload
	 *
	 * @param array $expectedPutObjectArg Expected arguments for PutObject call
	 * @param Throwable|null $putObjectException Exception to throw for PutObject call
	 *
	 * @return \Aws\S3\S3Client|MockObject Mock S3 client
	 */
	protected function getMockS3ClientForUpload(array $expectedPutObjectArg, ?Throwable $putObjectException): \Aws\S3\S3Client|MockObject
	{
		$mockBuilder = $this->getMockBuilder(\Aws\S3\S3Client::class);
		$mockBuilder->disableOriginalConstructor();
		$mockBuilder->disableAutoReturnValueGeneration();
		$mock = $mockBuilder->getMock();

		$putObjectCall = $mock->expects(self::exactly(1))->method('__call')->with('putObject', $expectedPutObjectArg);
		if ($putObjectException !== null)
			$putObjectCall->willThrowException($putObjectException);

		return $mock;
	}

	/**
	 * Get mock S3 client for download
	 *
	 * @param array $expectedGetObjectArg Expected arguments for GetObject call
	 * @param string|null $fileContents File contents
	 * @param Throwable|null $getObjectException Exception to throw for GetObject call
	 *
	 * @return \Aws\S3\S3Client|MockObject Mock S3 client
	 */
	protected function getMockS3ClientForDownload(array $expectedGetObjectArg, ?string $fileContents, ?Throwable $getObjectException): \Aws\S3\S3Client|MockObject
	{
		$mockBuilder = $this->getMockBuilder(\Aws\S3\S3Client::class);
		$mockBuilder->disableOriginalConstructor();
		$mockBuilder->disableAutoReturnValueGeneration();
		$mock = $mockBuilder->getMock();

		$getObjectCall = $mock->expects(self::exactly(1))->method('__call')->with('getObject', $expectedGetObjectArg);
		if ($fileContents !== null)
		{
			$getObjectCall->willReturn(new class($fileContents) {
				function __construct(protected $fileContents) {
				}
				function get() {
					return $this->fileContents;
				}
			});
		}
		if ($getObjectException !== null)
			$getObjectCall->willThrowException($getObjectException);

		return $mock;
	}
}
