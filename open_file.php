<?php
header('Content-Type: application/json');

$filename = isset($_GET['filename']) ? basename($_GET['filename']) : '';
$savedDir = __DIR__ . '/saved_scripts';

if (empty($filename)) {
    echo json_encode([
        'success' => false,
        'error' => 'No filename provided'
    ]);
    exit;
}

$filepath = $savedDir . '/' . $filename;

if (!file_exists($filepath)) {
    echo json_encode([
        'success' => false,
        'error' => 'File not found: ' . $filename
    ]);
    exit;
}

try {
    $content = file_get_contents($filepath);
    if ($content === false) {
        throw new Exception('Failed to read file');
    }
    
    echo json_encode([
        'success' => true,
        'content' => $content,
        'filename' => $filename
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error reading file: ' . $e->getMessage()
    ]);
}
