<?php

require_once __DIR__ . '/bootstrap.php';

$blobStorage = new AzureBlobStorage();
echo $blobStorage->createContainer('testing');

// use MicrosoftAzure\Storage\Blob\BlobRestProxy;

// $service = BlobRestProxy::createBlobService($_ENV['AZURE_STORAGE_CONNECTION_STRING']);
// $service->createContainer('testing');