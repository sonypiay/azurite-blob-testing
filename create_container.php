<?php

require_once __DIR__ . '/bootstrap.php';

$blobStorage = new AzureBlobStorage();
echo $blobStorage->createContainer('testing');