# AWS High Availability S3

This library makes it easy to write files to S3 with high availability.
It includes the following features:
- Upload an S3 file to the first successful file location.
- Download an S3 file that is available in multiple file locations.

## Usage

The APIs require you to pass in an `\Aws\Sdk` object.
In the examples below, `$awsSdk` is used for this object.

### Uploading a File

1. Create a list of available upload locations.
	- This is an `S3AvailableUploadFileBucketAndKeyLocations` object.
	- You can add anything that implements `S3FileBucketAndKeyProviderInterface` to the list of locations.
	- The simplest option is to use `S3FileBucketAndKey`, which implements this interface.
2. Set up `S3UploadFileMetadata` with metadata for the file to be uploaded.
3. Create an `AwsHighAvailabilityS3Uploader` object and call `uploadFileToS3`.
	- You can configure the uploader with an `AwsHighAvailabilityS3UploaderErrorHandlerInterface` to customize handling of failures. For example, you might want to use it to log the exception or you can throw an exception if you want to stop attempted alternative locations.

```php
// Set up possible locations
$primaryLocation = new S3FileBucketAndKey('us-east-1', 'phpunit-test-us-east-1', 'us-east-1/path/to/file.txt');
$backupLocation = new S3FileBucketAndKey('us-west-1', 'phpunit-test-us-west-1', 'us-west-1/path/to/file.txt');
$locations = new S3AvailableUploadFileBucketAndKeyLocations($primaryLocation);
$locations->addAlternativeLocation($backupLocation);

// Set up meta data
$metadata = new S3UploadFileMetadata();

// Upload
$s3Uploader = new AwsHighAvailabilityS3Uploader($awsSdk);
$finalLocation = $s3Uploader->uploadFileToS3($locations, 'File contents.', 'text/plain', $metadata);
```

### Downloading a File

1. Create a list of available download locations.
	- This is an `S3AvailableDownloadFileBucketAndKeyLocations` object.
	- You can add anything that implements `S3FileBucketAndKeyProviderInterface` to the list of locations.
	- The simplest option is to use `S3FileBucketAndKey`, which implements this interface.
2. Create an `AwsHighAvailabilityS3Downloader` object and call `downloadFileFromS3`.
	- You can configure the downloader with an `AwsHighAvailabilityS3DownloaderErrorHandlerInterface` to customize handling of failures. For example, you might want to use it to log the exception or you can throw an exception if you want to stop attempted alternative locations.

```php
// Set up possible locations
$primaryLocation = new S3FileBucketAndKey('us-east-1', 'phpunit-test-us-east-1', 'us-east-1/path/to/file.txt');
$backupLocation = new S3FileBucketAndKey('us-west-1', 'phpunit-test-us-west-1', 'us-west-1/path/to/file.txt');
$locations = new S3AvailableDownloadFileBucketAndKeyLocations($primaryLocation);
$locations->addAlternativeLocation($backupLocation);

// Download
$s3Downloader = new AwsHighAvailabilityS3Downloader($awsSdk);
$contents = $s3Downloader->downloadFileFromS3($locations);
```

### `S3UploadFileMetadata` Options
The `S3UploadFileMetadata` class allows you to customize the following:
- S3 storage class.
- S3 ACL (public vs private).
- Cache TTL for public objects.
