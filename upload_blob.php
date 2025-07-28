<?php

require_once __DIR__ . '/bootstrap.php';

$blogStorage = new AzureBlobStorage('uploads');

for( $i = 1; $i <= 10; $i++ ) {
    echo "Uploading file test{$i}.txt\n";
    $name = __DIR__ . "/files/test{$i}.txt";
    file_put_contents($name, "file {$i}");
    $blogStorage->uploadBlob($name);
}