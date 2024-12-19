<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$savedDir = __DIR__ . '/saved_scripts';

if (!file_exists($savedDir)) {
    mkdir($savedDir, 0777, true);
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
$filename = isset($_POST['filename']) ? basename($_POST['filename']) : (isset($_GET['filename']) ? basename($_GET['filename']) : '');
$newFilename = isset($_POST['newFilename']) ? basename($_POST['newFilename']) : '';

function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $data
    ));
    exit;
}

switch ($action) {
    case 'rename':
        if (empty($filename) || empty($newFilename)) {
            sendJsonResponse(false, 'Не указано имя файла');
        }

        // Добавляем расширение .py если его нет
        if (!str_ends_with(strtolower($newFilename), '.py')) {
            $newFilename .= '.py';
        }

        $oldPath = $savedDir . '/' . $filename;
        $newPath = $savedDir . '/' . $newFilename;

        if (!file_exists($oldPath)) {
            sendJsonResponse(false, 'Файл не найден');
        }

        if (file_exists($newPath)) {
            sendJsonResponse(false, 'Файл с таким именем уже существует');
        }

        if (rename($oldPath, $newPath)) {
            sendJsonResponse(true, 'Файл переименован', ['newFilename' => $newFilename]);
        } else {
            sendJsonResponse(false, 'Не удалось переименовать файл');
        }
        break;

    case 'delete':
        if (empty($filename)) {
            sendJsonResponse(false, 'Не указано имя файла');
        }

        $filePath = $savedDir . '/' . $filename;
        if (!file_exists($filePath)) {
            sendJsonResponse(false, 'Файл не найден');
        }

        if (unlink($filePath)) {
            sendJsonResponse(true, 'Файл удален');
        } else {
            sendJsonResponse(false, 'Не удалось удалить файл');
        }
        break;

    case 'download':
        if (empty($filename)) {
            sendJsonResponse(false, 'Не указано имя файла');
        }

        $filePath = $savedDir . '/' . $filename;
        if (!file_exists($filePath)) {
            sendJsonResponse(false, 'Файл не найден');
        }

        // Отправляем файл для скачивания
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        readfile($filePath);
        exit;
        break;

    default:
        sendJsonResponse(false, 'Неизвестное действие');
        break;
}
