<?php

namespace wenshizhengxin\unicloud_oss;

class UnicloudOSS
{
    private static $endpoint = '';
    private static $bucketName = '';
    private static $accessKey = '';
    private static $secretKey = '';
    private static $region = '';

    public static function setConfig($endpoint = 'oss-cn-north-2.unicloudsrv.com', $bucketName = '', $accessKey = '', $secretKey = '', $region = 'cn-beijing')
    {
        if (is_array($endpoint)) {
            $config = $endpoint;
            self::$endpoint = $config['endpoint'] ?? 'oss-cn-north-2.unicloudsrv.com';
            self::$bucketName = $config['bucket_name'] ?? '';
            self::$accessKey = $config['access_key'] ?? '';
            self::$secretKey = $config['secret_key'] ?? '';
            self::$region = $config['region'] ?? 'cn-beijing';
        } else {
            self::$endpoint = $endpoint;
            self::$bucketName = $bucketName;
            self::$accessKey = $accessKey;
            self::$secretKey = $secretKey;
            self::$region = $region;
        }
    }

    public static function uploadFiles()
    {
        $uploadPaths = [];
        $uploadUrls = [];
        foreach ($_FILES as $file) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $result = self::uploadFileByBase64(base64_encode(file_get_contents($file['tmp_name'])), $extension);

            $uploadPaths[] = $result['path'];
            $uploadUrls[] = $result['url'];
        }

        return ['path' => implode(',', $uploadPaths), 'url' => implode(',', $uploadUrls)];
    }

    public static function uploadFileByPath($localFilepath, $extension = null, $prefix = 'uploads')
    {
        if (!$extension) {
            $extension = array_reverse(explode('.', $localFilepath))[0];
        }
        $extension = ltrim($extension, '.');
        $prefix = rtrim($prefix, DIRECTORY_SEPARATOR);
        $content = file_get_contents($localFilepath);

        return self::uploadFileByContent($content, $extension, $prefix);
    }

    public static function uploadFileByContent($content, $extension, $prefix = 'uploads')
    {
        $prefix = rtrim($prefix, DIRECTORY_SEPARATOR);

        $s3Client = self::getS3Client();

        $destFilename = self::generateRandomFilename(substr($content, 0, 64), $extension);
        $filepath = $prefix . DIRECTORY_SEPARATOR . date('Ym') . DIRECTORY_SEPARATOR . $destFilename;
        $filepath = str_replace(DIRECTORY_SEPARATOR, '/', $filepath);
        // $filepath = $destFilename;

        $response = $s3Client->putObject([
            'Bucket' => self::$bucketName,
            'Key'    => $filepath,
            'Body'   => $content,
            'ACL'    => 'public-read',
        ]);

        if ($response->getStatusCode() != 200) {
            throw new \Exception('上传失败');
        }

        return ['url' => 'http://' . self::$bucketName . '.' . self::$endpoint . '/' . $filepath, 'path' => $filepath];
    }

    public static function uploadFileByBase64($base64, $extension, $prefix = 'uploads')
    {
        return self::uploadFileByContent(base64_decode($base64), $extension, $prefix);
    }

    public static function getS3Client()
    {
        $s3Client = new MyS3Client(self::$endpoint, self::$accessKey, self::$secretKey, self::$region);

        return $s3Client;
    }

    public static function generateRandomFilename($filepath, $extension = null)
    {
        if (!$extension) {
            $extension = array_reverse(explode('.', $filepath))[0];
        }
        $extension = ltrim($extension, '.');

        return md5($filepath) . '.' . $extension;
    }
}
