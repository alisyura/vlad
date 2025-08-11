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
        // 1. Проверка: POST и AJAX
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            http_response_code(403);
            exit;
        }

        header('Content-Type: application/json');

        // 2. Проверка CSRF
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Неверный CSRF-токен.']);
            exit;
        }

        // 3. Проверка наличия файла
        if (empty($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Файл не загружен.']);
            exit;
        }

        $file = $_FILES['file'];

        // 4. Проверка ошибки загрузки
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ошибка загрузки файла.']);
            exit;
        }

        // 5. Защита от поддельных путей
        if (!is_uploaded_file($file['tmp_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Файл не является загруженным.']);
            exit;
        }

        // 6. Проверка типа изображения
        $allowedImageTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];
        $imageType = exif_imagetype($file['tmp_name']);

        if ($imageType === false || !in_array($imageType, $allowedImageTypes)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Формат изображения не поддерживается.']);
            exit;
        }

        $imageMimeType = image_type_to_mime_type($imageType);

        // 7. Проверка размера файла
        $maxFileSize = (int) Config::getGlobalCfg('UploadedMaxFilesize'); // в байтах
        if ($file['size'] > $maxFileSize) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Размер файла превышает допустимый лимит.']);
            exit;
        }

        // 8. Получаем размеры
        $size = getimagesize($file['tmp_name']);
        if ($size === false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Не удалось определить размеры изображения.']);
            exit;
        }
        [$imageWidth, $imageHeight] = $size;

        // 9. Минимальные размеры
        $minWidth = (int) Config::getGlobalCfg('UploadedMinWidth');
        $minHeight = (int) Config::getGlobalCfg('UploadedMinHeight');
        if ($imageWidth < $minWidth || $imageHeight < $minHeight) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => "Минимальное разрешение: {$minWidth}x{$minHeight} пикселей."
            ]);
            exit;
        }

        // 10. Максимальные размеры и ресайз
        $maxWidth = (int) Config::getGlobalCfg('UploadedMaxWidth');
        $maxHeight = (int) Config::getGlobalCfg('UploadedMaxHeight');
        $resizeNeeded = ($imageWidth > $maxWidth || $imageHeight > $maxHeight);
        $fileToSave = $file['tmp_name']; // по умолчанию — оригинал
        $tempFile = null;

        if ($resizeNeeded) {
            $ratio = min($maxWidth / $imageWidth, $maxHeight / $imageHeight);
            $newWidth = (int)($imageWidth * $ratio);
            $newHeight = (int)($imageHeight * $ratio);

            // Загружаем исходное изображение
            $sourceImage = match($imageType) {
                IMAGETYPE_JPEG => imagecreatefromjpeg($file['tmp_name']),
                IMAGETYPE_PNG => imagecreatefrompng($file['tmp_name']),
                IMAGETYPE_GIF => imagecreatefromgif($file['tmp_name']),
                default => null
            };

            if (!$sourceImage) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Не удалось открыть изображение.']);
                exit;
            }

            // Создаём новое изображение
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Прозрачность для PNG и GIF
            if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            // Масштабируем
            imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $imageWidth, $imageHeight);

            // Освобождаем память
            imagedestroy($sourceImage);

            // Создаём временный файл
            $tempFile = tempnam(sys_get_temp_dir(), 'resized_');
            if (!$tempFile) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Не удалось создать временный файл.']);
                exit;
            }

            // Сохраняем обработанное изображение
            $success = match($imageType) {
                IMAGETYPE_JPEG => imagejpeg($resizedImage, $tempFile, 85),
                IMAGETYPE_PNG => imagepng($resizedImage, $tempFile),
                IMAGETYPE_GIF => imagegif($resizedImage, $tempFile),
            };

            imagedestroy($resizedImage);

            if (!$success) {
                if ($tempFile && file_exists($tempFile)) {
                    unlink($tempFile);
                }
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Ошибка при сохранении уменьшенного изображения.']);
                exit;
            }

            $fileToSave = $tempFile;
        }

        // 11. Очистка и генерация имени файла
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $safeName = $this->sanitizeFilename($originalName);
        $extension = match($imageType) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
        };

        // 12. Путь сохранения
        $baseUploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/' . trim(Config::getGlobalCfg('UploadDir'), '/') . '/';
        $yearDir = date('Y');
        $monthDir = date('m');
        $targetDir = $baseUploadDir . $yearDir . DIRECTORY_SEPARATOR . $monthDir . DIRECTORY_SEPARATOR;

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Не удалось создать каталог для загрузки.']);
                exit;
            }
        }

        // Уникальное имя
        $newFileName = "{$safeName}.{$extension}";
        $i = 1;
        while (file_exists($targetDir . $newFileName)) {
            $newFileName = "{$safeName}_{$i}.{$extension}";
            $i++;
        }
        $targetFile = $targetDir . $newFileName;

        // 13. Сохранение файла (обработанного или оригинала)
        if (!copy($fileToSave, $targetFile)) {
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Не удалось сохранить файл.']);
            exit;
        }

        // 14. Удаление временных файлов
        if ($tempFile && file_exists($tempFile)) {
            unlink($tempFile);
        }

        // 15. Альт-текст
        $altText = trim($_POST['alt'] ?? '');
        $altText = htmlspecialchars($altText, ENT_QUOTES, 'UTF-8');
        if (strlen($altText) > 255) {
            $altText = substr($altText, 0, 255);
        }

        // 16. URL для доступа
        $fileUrl = '/assets/uploads/' . $yearDir . '/' . $monthDir . '/' . $newFileName;

        // 17. Сохранение в БД
        try {
            $amm = new AdminMediaModel();
            $amm->saveImgToMedia(
                Auth::getUserId(),
                $fileUrl,
                filesize($targetFile),
                $imageMimeType,
                $altText
            );
        } catch (Exception $e) {
            // Удаляем файл, если не удалось сохранить в БД
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }
            Logger::error("Ошибка при сохранении в БД: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Ошибка при сохранении данных.']);
            exit;
        }

        // 18. Успех
        echo json_encode(['success' => true]);
        exit;
    }

    // Вспомогательная функция для очистки имени файла
    private function sanitizeFilename(string $name): string
    {
        // Транслитерация (реализуй свою или используй библиотеку)
        return transliterate($name);
    }
}

