<?php

// app/controllers/AdminMediaApiController.php


class AdminMediaApiController extends BaseAdminController
{
    private MediaService $mediaService;

    public function __construct(MediaService $mediaService, Request $request, 
        ResponseFactory $responseFactory)
    {
        parent::__construct($request, null, $responseFactory);
        $this->mediaService = $mediaService;
    }

    public function list(): Response
    {
        try {
            $media = $this->mediaService->list();

            return $this->renderJson('', 200, ['mediaList' => $media]);
        } catch (Throwable $e) {
            Logger::error('AdminMediaApiController.list. Сбой при получении списка картинок', [], $e);
            throw new HttpException('Сбой при получении списка картинок', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    public function upload(): Response
    {
        $file = [];
        $alt = '';
        
        try {
            $file = $this->request->file('file', []);
            $alt = $this->request->post('alt', '');

            $this->mediaService->upload($file, $alt);
            
            return $this->renderJson('Файл успешно загружен!');
        } catch (MediaException $e) {
            // Обработка ошибок, связанных только с загрузкой медиа
            Logger::error('AdminMediaApiController.Ошибка при загрузке картинки.', ['file' => $file, 'alt' => $alt], $e);
            throw new HttpException('Ошибка при загрузке: ' . $e->getMessage(), 400, $e, HttpException::JSON_RESPONSE);
        } catch (PDOException $e) {
            // Удаляем файл, если не удалось сохранить в БД
            Logger::error("AdminMediaApiController.Ошибка при сохранении в БД", ['file' => $file, 'alt' => $alt], $e);
            throw new HttpException('Ошибка при сохранении данных.', 500, $e, HttpException::JSON_RESPONSE);
        } catch (Throwable $e) {
            // Логируем ошибку и возвращаем ответ
            Logger::error("AdminMediaApiController.Сбой при загрузке файла", ['file' => $file, 'alt' => $alt], $e);
            throw new HttpException('Произошел сбой при загрузке файла.', 500, $e, HttpException::JSON_RESPONSE);
        } 
    }
}

