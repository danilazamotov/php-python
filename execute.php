<?php
// Включаем отображение всех ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Проверяем права на директорию
$dir = __DIR__;
if (!is_writable($dir)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => "Directory $dir is not writable"
    ]);
    exit;
}

// Проверяем права на лог файл
$logFile = __DIR__ . '/debug.log';
if (file_exists($logFile) && !is_writable($logFile)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => "Log file $logFile is not writable"
    ]);
    exit;
}

// Логирование
function logError($message) {
    $logFile = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "$timestamp - Execute.php - $message\n";
    
    if (@file_put_contents($logFile, $logMessage, FILE_APPEND) === false) {
        error_log("Failed to write to debug.log: " . error_get_last()['message']);
    }
}

// Конвертация в UTF-8
function convertToUtf8($text) {
    $encoding = mb_detect_encoding($text, ['UTF-8', 'Windows-1251', 'ASCII'], true);
    return $encoding ? iconv($encoding, 'UTF-8//IGNORE', $text) : $text;
}

// Отправка JSON ответа
function sendResponse($success, $output = '', $error = '') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Преобразуем вывод в UTF-8
    if (is_array($output)) {
        $output = implode("\n", $output);
    }
    
    $response = [
        'success' => $success,
        'output' => $output,
        'error' => $error
    ];
    
    logError("Sending response: " . json_encode($response));
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    logError("Starting execution");
    logError("Request method: " . $_SERVER['REQUEST_METHOD']);
    logError("Raw POST data: " . file_get_contents("php://input"));
    logError("POST variables: " . print_r($_POST, true));

    // Проверяем наличие кода
    if (!isset($_POST['code'])) {
        logError("No code provided");
        sendResponse(false, '', 'Код не предоставлен');
    }

    $code = $_POST['code'];
    logError("Received code: " . substr($code, 0, 100));

    if (empty(trim($code))) {
        logError("Empty code");
        sendResponse(false, '', 'Пустой код');
    }

    // Проверяем права на временную директорию
    $tempDir = sys_get_temp_dir();
    if (!is_writable($tempDir)) {
        logError("Temp directory $tempDir is not writable");
        sendResponse(false, '', "Нет прав на запись во временную директорию");
    }

    // Создаем временный файл
    $tempFile = @tempnam($tempDir, 'py_');
    if ($tempFile === false) {
        $error = error_get_last();
        logError("Failed to create temp file: " . $error['message']);
        sendResponse(false, '', 'Не удалось создать временный файл');
    }
    logError("Created temp file: " . $tempFile);

    // Добавляем расширение .py
    $pythonFile = $tempFile . '.py';
    if (!@rename($tempFile, $pythonFile)) {
        $error = error_get_last();
        logError("Failed to rename temp file: " . $error['message']);
        sendResponse(false, '', 'Не удалось переименовать временный файл');
    }
    logError("Renamed to: " . $pythonFile);

    // Добавляем кодировку UTF-8 в начало файла
    $codeWithEncoding = "# -*- coding: utf-8 -*-\n" . $code;
    
    // Записываем код в файл в UTF-8
    if (@file_put_contents($pythonFile, $codeWithEncoding) === false) {
        $error = error_get_last();
        logError("Failed to write code to file: " . $error['message']);
        sendResponse(false, '', 'Не удалось записать код во временный файл');
    }
    logError("Code written to file");

    // Проверяем, что файл существует и содержит код
    if (!file_exists($pythonFile)) {
        logError("Python file does not exist after writing");
        sendResponse(false, '', 'Файл не был создан');
    }
    logError("File exists and contains: " . file_get_contents($pythonFile));

    // Устанавливаем переменную окружения для Python
    putenv('PYTHONIOENCODING=utf8');
    
    // Выполняем Python скрипт с явным указанием кодировки
    $command = sprintf('chcp 65001 > nul && python -u "%s" 2>&1', $pythonFile);
    logError("Executing command: " . $command);
    
    $output = [];
    $returnCode = -1;
    
    exec($command, $output, $returnCode);
    
    logError("Execution complete. Return code: " . $returnCode);
    logError("Output: " . print_r($output, true));

    // Удаляем временный файл
    @unlink($pythonFile);
    logError("Temporary file deleted");

    // Отправляем ответ
    if ($returnCode === 0) {
        sendResponse(true, $output);
    } else {
        sendResponse(false, '', implode("\n", $output));
    }

} catch (Exception $e) {
    logError("Exception occurred: " . $e->getMessage());
    logError("Stack trace: " . $e->getTraceAsString());
    sendResponse(false, '', 'Ошибка: ' . $e->getMessage());
}
