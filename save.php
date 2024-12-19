<?php
// Включаем вывод ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

function sendJsonResponse($success, $message, $filename = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($filename !== null) {
        $response['filename'] = $filename;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Directory to save Python files
    $saveDir = __DIR__ . DIRECTORY_SEPARATOR . 'saved_scripts';

    // Create the directory if it doesn't exist
    if (!file_exists($saveDir)) {
        if (!mkdir($saveDir, 0777, true)) {
            sendJsonResponse(false, 'Не удалось создать директорию для сохранения');
        }
    }

    // Get the code and filename from POST request
    if (!isset($_POST['code'])) {
        sendJsonResponse(false, 'Код не предоставлен');
    }

    $code = $_POST['code'];
    if (empty($code)) {
        sendJsonResponse(false, 'Код не может быть пустым');
    }

    $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
    if (empty($filename)) {
        $filename = 'python_script_' . date('Y-m-d_H-i-s') . '.py';
    }

    // Remove any whitespace, newlines, etc
    $filename = trim(preg_replace('/\s+/', ' ', $filename));

    // Ensure filename has .py extension
    if (!str_ends_with(strtolower($filename), '.py')) {
        $filename .= '.py';
    }

    // Clean filename to prevent directory traversal and invalid characters
    $filename = basename($filename);
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
    
    if (empty($filename)) {
        $filename = 'python_script_' . date('Y-m-d_H-i-s') . '.py';
    }
    
    // Формируем полный путь к файлу
    $filepath = $saveDir . DIRECTORY_SEPARATOR . $filename;

    error_log("Trying to save to: " . $filepath);

    // Save the code to file
    if (file_put_contents($filepath, $code) === false) {
        error_log("Failed to write to file: " . $filepath);
        error_log("Error: " . error_get_last()['message']);
        sendJsonResponse(false, 'Не удалось записать файл');
    }

    // Verify that the file was created
    if (!file_exists($filepath)) {
        sendJsonResponse(false, 'Файл не был создан');
    }

    // Return success response
    sendJsonResponse(true, 'Код успешно сохранен как: ' . $filename, $filename);

} catch (Exception $e) {
    // Log error for debugging
    error_log('Save error: ' . $e->getMessage());
    sendJsonResponse(false, 'Ошибка сохранения: ' . $e->getMessage());
}
