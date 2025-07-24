<?php

require_once __DIR__ . '/bootstrap.php';

$blogStorage = new AzureBlobStorage('uploads');

echo $blogStorage->listBlobs();