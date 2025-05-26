<?php

require_once realpath(__DIR__ . '/vendor/autoload.php');// Include the AWS SDK for PHP
require_once('environment.php');

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class AWSUploader {

    private $aws_id;
    private $aws_key;
    private $s3_bucket;
    private $s3_region;

    public function __construct() {
        $this->aws_id = $_ENV['AWS_ACCESS_KEY_ID'];
        $this->aws_key = $_ENV['AWS_SECRET_ACCESS_KEY'];
        $this->s3_bucket = $_ENV['S3_BUCKET'];
        $this->s3_region = $_ENV['S3_REGION'];
    }

    public function uploadFile($folder, $file) {
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => $this->s3_region,
            // We need this since in the deployable application can't acccess env values
            'credentials' => [
                 'key' => $this->aws_id,
                 'secret' => $this->aws_key,
             ]
        ]);

        $metadata = stream_get_meta_data($file);
        $filename=$metadata['uri'];
        $fileKey = $folder . '/' . $filename;
        try {
            $result = $s3->putObject([
                'Bucket' => $this->s3_bucket,
                'Key' => $fileKey,
                'Body' => $file,
                'ACL' => 'public-read',
            ]);

            return $fileKey;
        } catch (AwsException $e) { // Catching generic AwsException is better for S3
            // Error occurred while uploading the file to S3
            error_log("S3Exception in AWSUploader::uploadFile: " . $e->getMessage() . " | Attempted File key: " . $fileKey);
            return false;
        }
    }

    public function getFile($fileKey) {
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => $this->s3_region
            // AWS SDK will automatically look for credentials in environment variables
        ]);

        try {
            $result = $s3->getObject([
                'Bucket' => $this->s3_bucket, // Corrected: use class property
                'Key' => $fileKey,
            ]);

            $fileContent = $result['Body'];
            $fileName = basename($fileKey);
            file_put_contents($fileName, $fileContent);

            return $fileName;
        } catch (AwsException $e) { // Catching generic AwsException is better for S3
            // Error occurred while retrieving the file from S3
            error_log("S3Exception in AWSUploader::getFile: " . $e->getMessage() . " | File key: " . $fileKey);
            return false; // Returning false for consistency
        }
    }
}

// // Usage example:
// $awsUploader = new AWSUploader('your_aws_key', 'your_aws_secret', 'your_bucket_name', 'your_region');

// // Upload file
// $folder = 'your_folder';
// $file = $_FILES['file']; // Assuming the file input field name is "file"
// $fileKey = $awsUploader->uploadFile($folder, $file);

// // Get file
// $fileKey = 'your_file_key';
// $fileName = $awsUploader->getFile($fileKey);
