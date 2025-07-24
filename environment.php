<?php

require_once __DIR__ . '/bootstrap.php';

echo json_encode($_ENV, JSON_PRETTY_PRINT);
echo "\n";