<?php

namespace wenshizhengxin\unicloud_oss;

use Aws\Api\TimestampShape;
use Aws\Credentials\Credentials;
use Aws\Signature\SignatureV4;
use cmq2080\mime_type_getter\MIMEType;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class MyS3Client
{
    private $endpoint = '';
    private $accessKey = '';
    private $secretKey = '';
    private $region = '';
    private $serviceName = 's3';

    private $credentials = null;

    public function __construct($endpoint, $accessKey, $secretKey, $region)
    {
        $this->endpoint = $endpoint;
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->region = $region;

        $this->credentials = new Credentials($this->accessKey, $this->secretKey);
    }

    public function putObject(array $args = [])
    {
        $bucketName = $args['Bucket'];
        $objectKey = $args['Key'];
        $content = $args['Body'];
        $acl = $args['ACL'];

        $client = new Client();
        $host = "http://{$bucketName}.{$this->endpoint}";
        $url =  $host . '/' . $objectKey;
        $extension = array_reverse(explode('.', $objectKey))[0];
        $contentType = MIMEType::getMIMETypeByExtension($extension, 'text/plain');
        $request = new Request('PUT', $url, [
            'Date' => TimestampShape::format(time(), 'rfc822'),
            'Content-Type' => $contentType,
            'Content-Length' => strlen($content),
            'x-amz-acl' => $acl,
            SignatureV4::AMZ_CONTENT_SHA256_HEADER => ['UNSIGNED-PAYLOAD'],
        ], $content);

        $signature = new SignatureV4($this->serviceName, $this->region, ['use_v4a' => false]);
        $request = $signature->signRequest($request, $this->credentials, $this->serviceName);
        // var_dump($request);
        // exit;

        return $client->send($request);
    }
}
