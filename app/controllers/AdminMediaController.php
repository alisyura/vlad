<?php

// app/controllers/AdminMediaController.php


class AdminMediaController extends AdminController
{
    public function lll()
    {
        header('Content-Type: application/json');
        echo json_encode(['msg' => 'worked']);
        exit;
    }


    public function list()
    {
        // Проверяем, что это AJAX-запрос
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            http_response_code(403);
            exit;
        }

        $amm = new AdminMediaModel();
        $media = $amm->getMedialist();

        $mediaStruct = print_r($media, true);
        Logger::debug($mediaStruct);

        header('Content-Type: application/json');
        echo json_encode($media);
        exit;
    }

    public function upload()
    {
        // Проверяем, что это POST и AJAX-запрос
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            http_response_code(403);
            exit;
        }

        // Проверяем CSRF-токен
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid CSRF token.']);
            exit;
        }

        if (empty($_FILES['file'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No file uploaded.']);
            exit;
        }

        $file = $_FILES['file'];

        // --- Новая логика для создания уникального пути и имени файла ---
        $baseUploadDir = Config::getGlobalCfg('UploadDir');
        $yearDir = date('Y');
        $monthDir = date('m');

        // Создаем путь к каталогу: /assets/uploads/2025/08/
        $targetDir = $baseUploadDir . $yearDir . DIRECTORY_SEPARATOR . $monthDir . DIRECTORY_SEPARATOR;

        // Создаем каталоги, если они не существуют
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = basename($file['name']);
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $fileNameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);
        $newFileName = $fileName;

        $i = 1;
        while (file_exists($targetDir . $newFileName)) {
            $newFileName = $fileNameWithoutExt . '_' . $i . '.' . $fileExtension;
            $i++;
        }
        $targetFile = $targetDir . $newFileName;
        // --- Конец новой логики ---

        // Проверяем, что файл является изображением
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
        if (!in_array(strtolower($fileExtension), $allowedTypes)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Only JPG, JPEG, PNG, GIF, & SVG files are allowed.']);
            exit;
        }
        $fileUrl = '/assets/uploads/' . $yearDir . '/' . $monthDir . '/' . $newFileName; 
$destFile = $_SERVER['DOCUMENT_ROOT'].$fileUrl;
        // Проверяем, что файл успешно перемещён
        if (move_uploaded_file($file['tmp_name'], $destFile)) {
            // Файл успешно загружен. Теперь записываем в базу данных.
            // URL, по которому будет доступен файл
            $fileUrl = '/assets/uploads/' . $yearDir . '/' . $monthDir . '/' . $newFileName; 
            
            // Здесь должна быть логика сохранения в таблицу media.
            // Например: DB::insert('INSERT INTO media (url, alt) VALUES (?, ?)', [$fileUrl, $newFileName]);

            header('Content-Type: application/json');
            echo json_encode(
                [
                    'success' => true, 
                    'url' => $fileUrl, 
                    'destFile' => $destFile,
                    'targetFile' => $targetFile
                ]);
            exit;
        } else {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to upload file.']);
            exit;
        }
    }
}

