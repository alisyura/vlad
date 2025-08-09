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

        header('Content-Type: application/json');
        // Проверяем CSRF-токен
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false,'error' => 'Неверный токен.']);
            exit;
        }

        if (empty($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['success' => false,'error' => 'Файл не загружен.']);
            exit;
        }

        $file = $_FILES['file'] ?? null;
        // 1. Проверка на отсутствие файла или ошибку загрузки
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ошибка загрузки файла.']);
            exit;
        }

        if (!is_uploaded_file($file['tmp_name']))
        {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Файл не является загруженным файлом.'
            ]);
            exit;
        }

        // Массив разрешенных типов изображений
        $allowedImageTypes = [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF];
        // Получаем реальный тип изображения из файла
        $imageType = exif_imagetype($file['tmp_name']);
        // Проверяем, что тип изображения есть в нашем списке
        if ($imageType === false || !in_array($imageType, $allowedImageTypes)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Формат изображения не поддерживается.'
            ]);
            exit;
        }
        $imageMimeType = image_type_to_mime_type($imageType);

        if ($file['size'] > Config::getGlobalCfg('UploadedMaxFilesize')) { // 2 MB
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Размер файла превышает 2 Мб'
            ]);
            exit;
        }

        // Получаем размеры изображения
        $imageInfo = getimagesize($file['tmp_name']);
        $imageWidth = $imageInfo[0];
        $imageHeight = $imageInfo[1];

        // Устанавливаем максимальные допустимые размеры
        $maxWidth = Config::getGlobalCfg('UploadedMaxWidth');
        $maxHeight = Config::getGlobalCfg('UploadedMaxHeight');

        if ($imageWidth > $maxWidth || $imageHeight > $maxHeight) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => "Размер изображения превышает допустимые значения ${maxWidth}x${maxHeight}."
            ]);
            exit;
        }

        // --- Новая логика для создания уникального пути и имени файла ---
        $baseUploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/' . rtrim(Config::getGlobalCfg('UploadDir'), '/') . '/';
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
        $fileNameWithoutExt = transliterate(pathinfo($fileName, PATHINFO_FILENAME));
        $newFileName = "${fileNameWithoutExt}.${fileExtension}";

        $i = 1;
        while (file_exists($targetDir . $newFileName)) {
            $newFileName = $fileNameWithoutExt . '_' . $i . '.' . $fileExtension;
            $i++;
        }
        $targetFile = $targetDir . $newFileName;
        // --- Конец новой логики ---

        // Проверяем, что файл успешно перемещён
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        
            // Файл успешно загружен. Теперь записываем в базу данных.
            // URL, по которому будет доступен файл
            $fileUrl = '/assets/uploads/' . $yearDir . '/' . $monthDir . '/' . $newFileName; 
            
            try
            {
                $amm = new AdminMediaModel();
                $amm->saveImgToMedia(Auth::getUserId(), $fileUrl,
                    $file['size'], $imageMimeType);
            }
            catch(Exception $e)
            {
                Logger::error("Ошибка при сохранении файла $targetFile в таблицу media " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Ошибка при сохранении файла.']);
                exit;
            }

            echo json_encode(['success' => true]);
            exit;
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Сбой при загрузке файла.']);
            exit;
        }
    }
}

