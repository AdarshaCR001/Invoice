<?php

require_once realpath(__DIR__ . '/vendor/autoload.php');// Include the AWS SDK for PHP
require_once('environment.php');

use AsyncAws\S3\S3Client;
use AsyncAws\S3\S3Exception;

class AWSUploader {

    private $aws_id;
    private $aws_key;
    private $s3_bucket;
    private $s3_region;

    public function __construct() {
        $this->aws_id = $_ENV['AWS_Id'];
        $this->aws_key = $_ENV['AWS_Key'];
        $this->s3_bucket = $_ENV['S3_BUCKET'];
        $this->s3_region = $_ENV['S3_REGION'];
    }

    public function uploadFile($folder, $file) {
        $s3 = new S3Client([
            'region' => $this->s3_region,
            'accessKeyId' => $this->aws_id,
                'accessKeySecret' => $this->aws_key
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
        } catch (S3Exception $e) {
            // Error occurred while uploading the file to S3
            echo 'Error: ' . $e->getMessage();
        }
    }

    public function getFile($fileKey) {
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => [
                'key' => $this->awsKey,
                'secret' => $this->awsSecret,
            ],
        ]);

        try {
            $result = $s3->getObject([
                'Bucket' => $this->bucketName,
                'Key' => $fileKey,
            ]);

            $fileContent = $result['Body'];
            $fileName = basename($fileKey);
            file_put_contents($fileName, $fileContent);

            return $fileName;
        } catch (S3Exception $e) {
            // Error occurred while retrieving the file from S3
            echo 'Error: ' . $e->getMessage();
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
