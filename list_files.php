<?php
header('Content-Type: application/json');

$savedDir = __DIR__ . '/saved_scripts';

if (!file_exists($savedDir)) {
    mkdir($savedDir);
}

$files = array_filter(scandir($savedDir), function($file) {
    return $file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'py';
});

echo json_encode(array(
    'success' => true,
    'files' => array_values($files)
));
