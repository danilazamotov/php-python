<?php
header('Content-Type: application/json');

function formatPythonCode($code) {
    // Create a temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'py_format_');
    file_put_contents($tempFile, $code);
    
    // Use black formatter
    $command = sprintf('python -m black %s -q', escapeshellarg($tempFile));
    exec($command, $output, $returnValue);
    
    if ($returnValue === 0) {
        $formattedCode = file_get_contents($tempFile);
        unlink($tempFile);
        return array('success' => true, 'formatted' => $formattedCode);
    }
    
    unlink($tempFile);
    return array('success' => false, 'error' => 'Failed to format code');
}

$code = isset($_POST['code']) ? $_POST['code'] : '';

if (empty($code)) {
    echo json_encode(array('success' => false, 'error' => 'No code provided'));
    exit;
}

echo json_encode(formatPythonCode($code));
