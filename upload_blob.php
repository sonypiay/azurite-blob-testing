<?php

require_once __DIR__ . '/bootstrap.php';

$blogStorage = new AzureBlobStorage('uploads');

$filename = 'composer.json';
$filepath = __DIR__ . '/' . $filename;
echo $blogStorage->uploadBlob($filename, $filepath);