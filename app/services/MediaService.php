<?php
// app/services/MediaUploadService.php

class MediaService
{
    private AdminMediaModel $model;
    private AuthService $authService;

    public function __construct(AdminMediaModel $model, AuthService $authService)
    {
        $this->model = $model;
        $this->authService = $authService;
    }

    public function list(): array
    {
        return $this->model->getMedialist();
    }

    public function upload(array $file, string $alt): array
    {
        if (empty($file)) {
            throw new MediaException('Файл не получен', MediaException::FILE_NOT_UPLOADED_OR_NOT_FOUND);
        }
        try {
            $uploadedFile = $this->handleUpload($file);

            $this->model->saveImgToMedia(
                $this->authService->getUserId(),
                $uploadedFile['url'],
                $uploadedFile['size'],
                $uploadedFile['mime'],
                trim($alt)
            );

            return $uploadedFile;
        } catch (PDOException $e) {
            $targetFile = $uploadedFile['targetFile'] ?? '';
            if (!empty($targetFile) && file_exists($targetFile)) {
                unlink($targetFile);
            }
            throw $e;
        }
    }

    /**
     * Обрабатывает загруженный файл и сохраняет его.
     * @param array $file Массив $_FILES['...']
     * @return array Массив с данными о сохраненном файле.
     * @throws Exception Если произошла ошибка валидации или сохранения.
     */
    public function handleUpload(array $file): array
    {
        // 1. Общие проверки файла (ошибки загрузки, наличие)
        $this->validateFile($file);

        // 2. Валидация изображения
        $imageData = $this->validateImage($file);
        
        // 3. Обработка и сохранение файла
        $uploadedFile = $this->processAndSave($file, $imageData);

        return $uploadedFile;
    }

    private function validateFile(array $file): void
    {
        // Самая важная проверка: файл должен существовать и быть легитимно загружен.
        if (empty($file) || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new MediaException('Файл не был загружен или не является допустимым.', MediaException::FILE_NOT_UPLOADED_OR_NOT_FOUND);
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new MediaException('Ошибка загрузки файла.', MediaException::UPLOAD_ERROR);
        }
    }

    private function validateImage(array $file): array
    {
        // Проверка размера файла
        $maxFileSize = (int) Config::get('upload.UploadedMaxFilesize');
        if ($file['size'] > $maxFileSize) {
            throw new MediaException('Размер файла превышает допустимый лимит.', MediaException::FILE_SIZE_EXCEEDS_LIMIT);
        }

        // Проверка типа изображения
        $allowedImageTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];
        $imageType = exif_imagetype($file['tmp_name']);

        if ($imageType === false || !in_array($imageType, $allowedImageTypes)) {
            throw new MediaException('Формат изображения не поддерживается.', MediaException::INVALID_IMAGE_TYPE);
        }

        // Теперь можно безопасно использовать getimagesize()
        // Проверка размеров изображения
        $size = getimagesize($file['tmp_name']);
        if ($size === false) {
            throw new MediaException('Не удалось определить размеры изображения.', MediaException::IMAGE_DIMENSIONS_DETECTION_ERROR);
        }
        [$imageWidth, $imageHeight] = $size;
        
        $minWidth = (int) Config::get('upload.UploadedMinWidth');
        $minHeight = (int) Config::get('upload.UploadedMinHeight');
        if ($imageWidth < $minWidth || $imageHeight < $minHeight) {
            throw new MediaException("Минимальное разрешение: {$minWidth}x{$minHeight} пикселей.", MediaException::IMAGE_DIMENSIONS_TOO_SMALL);
        }

        return [
            'imageWidth' => $imageWidth,
            'imageHeight' => $imageHeight,
            'imageType' => $imageType
        ];   
    }

    private function processAndSave(array $file, array $imageData): array
    {
        ['imageWidth' => $imageWidth, 'imageHeight' => $imageHeight, 'imageType' => $imageType] = $imageData;

        $maxWidth = (int) Config::get('upload.UploadedMaxWidth');
        $maxHeight = (int) Config::get('upload.UploadedMaxHeight');
        
        // Определяем необходимость ресайза
        $resizeNeeded = ($imageWidth > $maxWidth || $imageHeight > $maxHeight);
        $fileToSave = $file['tmp_name'];
        $tempFile = null;

        if ($resizeNeeded) {
            $fileToSave = $this->resizeImage($imageWidth, $imageHeight, $maxWidth, 
                $maxHeight, $imageType, $fileToSave);
        }

        // 11. Очистка и генерация имени файла
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $safeName = transliterate($originalName);
        $extension = match($imageType) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
        };
        
        // 12. Путь сохранения
        $baseUploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/' . trim(Config::get('upload.UploadDir'), '/') . '/';
        $yearDir = date('Y');
        $monthDir = date('m');
        $targetDir = $baseUploadDir . $yearDir . DIRECTORY_SEPARATOR . $monthDir . DIRECTORY_SEPARATOR;

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                throw new MediaException('Не удалось создать каталог для загрузки.', MediaException::DIRECTORY_CREATION_FAILED);
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
        
        // 13. Сохранение файла
        if (!copy($fileToSave, $targetFile)) {
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }
            throw new MediaException('Не удалось сохранить файл.', MediaException::FILE_SAVE_ERROR);
        }

        // 14. Удаление временных файлов
        if ($tempFile && file_exists($tempFile)) {
            unlink($tempFile);
        }

        return [
            'url' => '/assets/uploads/' . $yearDir . '/' . $monthDir . '/' . $newFileName,
            'size' => filesize($targetFile),
            'mime' => image_type_to_mime_type($imageType),
            'targetFile' => $targetFile
        ];
    }
    
    private function resizeImage($imageWidth, $imageHeight, $maxWidth, $maxHeight, $imageType, $file_tmp_name): string
    {
        $ratio = min($maxWidth / $imageWidth, $maxHeight / $imageHeight);
        $newWidth = (int)($imageWidth * $ratio);
        $newHeight = (int)($imageHeight * $ratio);

        // Загружаем исходное изображение
        $sourceImage = match($imageType) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($file_tmp_name),
            IMAGETYPE_PNG => imagecreatefrompng($file_tmp_name),
            IMAGETYPE_GIF => imagecreatefromgif($file_tmp_name),
            default => null
        };
        if (!$sourceImage) {
            throw new MediaException('Не удалось открыть изображение.', MediaException::IMAGE_COULD_NOT_BE_OPENED);
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
            throw new MediaException('Не удалось создать временный файл.', MediaException::TEMP_FILE_CREATION_ERROR);
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
            throw new MediaException('Не удалось создать временный файл.', MediaException::FILE_SAVE_ERROR);
        }

        return $tempFile;
    }
}