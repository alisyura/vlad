<?php

// app/controllers/AdminMediaApiController.php


class AdminMediaApiController extends BaseController
{
    public function list()
    {
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
        // Устанавливаем заголовок JSON
        header('Content-Type: application/json');
        // Переменная для хранения пути к файлу
        $targetFile = null;
        
        try {
            // Создаем сервис для загрузки
            $mediaUploadService = new MediaUploadService();
            
            // Передаём ему файл для обработки
            $uploadedFile = $mediaUploadService->handleUpload($_FILES['file']);
            

            // Получаем модель и сохраняем данные в БД
            $amm = new AdminMediaModel();
            $amm->saveImgToMedia(
                Auth::getUserId(),
                $uploadedFile['url'],
                $uploadedFile['size'],
                $uploadedFile['mime'],
                trim($_POST['alt'] ?? '')
            );

            
            echo json_encode(['success' => true, 'message' => 'Файл успешно загружен!']);
            
        } catch (MediaException $e) {
            // Обработка ошибок, связанных только с загрузкой медиа
            Logger::error($e->getTraceAsString());
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
        } catch (PDOException $e) {
            // Удаляем файл, если не удалось сохранить в БД
            $targetFile = $uploadedFile['targetFile'];
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }
            Logger::error("Ошибка при сохранении в БД: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении данных.']);
        } catch (Exception $e) {
            // Логируем ошибку и возвращаем ответ
            Logger::error($e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Произошла непредвиденная ошибка']);
        } 
        exit;
    }
}

