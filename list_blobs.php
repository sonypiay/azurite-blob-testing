<?php

require_once __DIR__ . '/bootstrap.php';

$blogStorage = new AzureBlobStorage('uploads');

$result = $blogStorage->listBlobs();
$xmlObject = simplexml_load_string($result);

echo json_encode($xmlObject, JSON_PRETTY_PRINT);