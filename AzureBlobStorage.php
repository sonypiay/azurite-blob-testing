<?php

class AzureBlobStorage
{
    private string $accountName;
    private string $accountKey;
    private string $endpoint;
    private string $version = '2019-12-12';
    public string $containerName = 'default';

    public function __construct( ?string $containerName = '' )
    {
        $this->containerName = ! empty( $containerName ) ? $containerName : $this->containerName;
        $this->accountName = $_ENV['AZURE_STORAGE_ACCOUNT_NAME'];
        $this->accountKey = $_ENV['AZURE_STORAGE_ACCOUNT_KEY'];
        $this->endpoint = $_ENV['AZURE_STORAGE_BLOB_ENDPOINT'];
    }

    public function setContainerName(string $containerName): void
    {
        $this->containerName = $containerName;
    }

    public function createSignatureSharedKey($url, $method, $headers)
    {
        $urlResource = $this->canonicalizedResource($url);
        $headersResource = $this->canonicalizedHeaders($headers);

        $contentType = isset( $headers['Content-Type'] ) ? $headers['Content-Type'] : "";
        $contentLength = isset( $headers['Content-Length'] ) ? $headers['Content-Length'] : "";

        $arraySign = [
            strtoupper($method), // HTTP Verb
            "", // Content-Encoding,
            "", // Content-Language
            $contentLength, // Content-Length,
            "", // Content-MD5
            $contentType, // Content-Type
            "", // Date
            "", // If-Modified-Since
            "", // If-Match
            "", // If-None-Match
            "", // If-Unmodified-Since
            "", // Range
            $headersResource, // Canonicalized Headers
            $urlResource, // Canonicalized Resources
        ];

        $stringToSign = implode("\n", $arraySign);
        
        // Hashing the string to sign dengan HMAC-SHA256
        $decodedKey = base64_decode($this->accountKey);
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, $decodedKey, true));

        return $signature;
    }

    private function canonicalizedResource($url)
    {
        $parseUrl = parse_url($url);
        $query = [];

        parse_str($parseUrl['query'] ?? '', $query);
        ksort($query);

        $queryString = [];

        foreach( $query as $k => $v ) {
            $queryString[] = strtolower($k) . ":{$v}";
        }

        $path = $parseUrl['path'];
        $resource = "/" . $this->accountName . $path;
        
        if( ! empty($queryString) ) {
            $resource .= "\n" . implode("\n", $queryString);
        }

        return $resource;
    }

    public function canonicalizedHeaders($headers = [])
    {
        $msHeaders = [];

        foreach( $headers as $k => $v ) {
            if ( str_starts_with(strtolower($k), 'x-ms-') ) {
                $msHeaders[strtolower($k)] = trim($v);
            }
        }

        ksort($msHeaders);
        $headersResource = [];

        foreach( $msHeaders as $k => $v ) {
            $headersResource[$k] = "{$k}:{$v}";
        }

        return implode("\n", $headersResource);
    }

    public function createContainer($containerName)
    {
        date_default_timezone_set('UTC');
        $method = "PUT";
        $currentDate = gmdate('D, d M Y H:i:s T');
        $url = "{$this->endpoint}/{$containerName}?restype=container";
        $headers = [
            "x-ms-version" => $this->version,
            "x-ms-date" => $currentDate,
        ];
        $signature = $this->createSignatureSharedKey($url, $method, $headers);

        $headersCurl = [
            "Authorization: SharedKey {$this->accountName}:{$signature}"
        ];

        foreach( $headers as $k => $v ) {
            $headersCurl[] = "{$k}: {$v}";
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headersCurl,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function listBlobs()
    {
        date_default_timezone_set('UTC');
        $method = "GET";
        $currentDate = gmdate('D, d M Y H:i:s T');
        $url = "{$this->endpoint}/{$this->containerName}?restype=container&comp=list";

        $headers = [
            "x-ms-version" => $this->version,
            "x-ms-date" => $currentDate,
        ];
        $signature = $this->createSignatureSharedKey($url, $method, $headers);

        $headersCurl = [
            "Authorization: SharedKey {$this->accountName}:{$signature}"
        ];

        foreach( $headers as $k => $v ) {
            $headersCurl[] = "{$k}: {$v}";
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headersCurl,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function uploadBlob(string $filePath)
    {
        date_default_timezone_set('UTC');
        $fileInfo = pathinfo($filePath);
        $blobName = $fileInfo['basename'];

        $url = "{$this->endpoint}/{$this->containerName}/{$blobName}";
        $method = "PUT";
        $currentDate = gmdate('D, d M Y H:i:s T');
        $headers = [
            "x-ms-version" => $this->version,
            "x-ms-date" => $currentDate,
            "x-ms-blob-type" => "BlockBlob",
            "Content-Length" => filesize($filePath),
            "Content-Type" => mime_content_type($filePath),
        ];
        $signature = $this->createSignatureSharedKey($url, $method, $headers);
        $headersCurl = [
            "Authorization: SharedKey {$this->accountName}:{$signature}"
        ];

        foreach( $headers as $k => $v ) {
            $headersCurl[] = "{$k}: {$v}";
        }

        $fileHandle = fopen($filePath, 'r');
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headersCurl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $fileHandle,
            CURLOPT_INFILESIZE => filesize($filePath)
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        fclose($fileHandle);

        return $response;
    }
}