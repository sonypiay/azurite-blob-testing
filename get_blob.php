<?php

require_once __DIR__ . '/bootstrap.php';

$filename = "duh_jadi_malu.jpg";
$blogStorage = new AzureBlobStorage('uploads');
echo $blogStorage->getBlob($filename) . "\n";