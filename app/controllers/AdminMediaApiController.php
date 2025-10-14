<?php

// app/controllers/AdminMediaApiController.php


class AdminMediaApiController extends BaseController
{
    use JsonResponseTrait;

    private MediaService $mediaService;

    public function __construct(MediaService $mediaService, Request $request)
    {
        parent::__construct($request, null);
        $this->mediaService = $mediaService;
    }

    public function list()
    {
        try {
            $media = $this->mediaService->list();

            $this->sendSuccessJsonResponse('', 200, ['mediaList' => $media]);
        } catch (Throwable $e) {
            Logger::error('AdminMediaApiController.list. Сбой при получении списка картинок', [], $e);
            $this->sendErrorJsonResponse('Сбой при получении списка картинок', 500);
        }

        exit;
    }

    public function upload()
    {
        $file = [];
        $alt = '';
        
        try {
            $file = $this->request->file('file', []);
            $alt = $this->request->post('alt', '');

            $this->mediaService->upload($file, $alt);
            
            $this->sendSuccessJsonResponse('Файл успешно загружен!');
        } catch (MediaException $e) {
            // Обработка ошибок, связанных только с загрузкой медиа
            Logger::error('AdminMediaApiController.Ошибка при загрузке картинки.', ['file' => $file, 'alt' => $alt], $e);
            $this->sendErrorJsonResponse('Ошибка при загрузке: ' . $e->getMessage(), 400);
        } catch (PDOException $e) {
            // Удаляем файл, если не удалось сохранить в БД
            Logger::error("AdminMediaApiController.Ошибка при сохранении в БД", ['file' => $file, 'alt' => $alt], $e);
            $this->sendErrorJsonResponse('Ошибка при сохранении данных.', 500);
        } catch (Throwable $e) {
            // Логируем ошибку и возвращаем ответ
            Logger::error("AdminMediaApiController.Сбой при загрузке файла", ['file' => $file, 'alt' => $alt], $e);
            $this->sendErrorJsonResponse('Произошел сбой при загрузке файла.', 500);
        } 
        exit;
    }
}

