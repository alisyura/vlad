<?php

// app/controllers/AdminMediaApiController.php


class AdminMediaApiController extends BaseAdminController
{
    private MediaService $mediaService;
    private ErrorResponseFactory $errorResponseFactory;

    public function __construct(MediaService $mediaService, Request $request, 
        ResponseFactory $responseFactory, ErrorResponseFactory $errorResponseFactory)
    {
        parent::__construct($request, null, $responseFactory);
        $this->mediaService = $mediaService;
        $this->errorResponseFactory = $errorResponseFactory;
    }

    public function list(): Response
    {
        $curPage = $this->getRequest()->page;

        try {
            $curPage = filter_var($curPage, FILTER_VALIDATE_INT, [
                'options' => [
                    'min_range' => 1
                ]
            ]);
            if ($curPage === false) {
                $curPage = 1;
            }
            
            $media = $this->mediaService->list($curPage);

            return $this->renderJson('', 200, $media);
        } catch (Throwable $e) {
            Logger::error('AdminMediaApiController.list. Сбой при получении списка картинок', ['curPage' => $curPage], $e);
            throw new HttpException('Сбой при получении списка картинок', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    public function upload(): Response
    {
        $file = [];
        $alt = '';
        
        try {
            $file = $this->getRequest()->file('file', []);
            $alt = $this->getRequest()->post('alt', '');

            $this->mediaService->upload($file, $alt);

            return $this->renderJson('Файл успешно загружен!');
        } catch (MediaException $e) {
            // Обработка ошибок, связанных только с загрузкой медиа
            Logger::error('AdminMediaApiController.upload Ошибка при загрузке картинки.', ['file' => $file, 'alt' => $alt], $e);
            throw new HttpException('Ошибка при загрузке: ' . $e->getMessage(), 400, $e, HttpException::JSON_RESPONSE);
        } catch (PDOException $e) {
            // Удаляем файл, если не удалось сохранить в БД
            Logger::error("AdminMediaApiController.upload Ошибка при сохранении в БД", ['file' => $file, 'alt' => $alt], $e);
            throw new HttpException('Ошибка при сохранении данных.', 500, $e, HttpException::JSON_RESPONSE);
        } catch (Throwable $e) {
            // Логируем ошибку и возвращаем ответ
            Logger::error("AdminMediaApiController.upload Сбой при загрузке файла", ['file' => $file, 'alt' => $alt], $e);
            throw new HttpException('Произошел сбой при загрузке файла.', 500, $e, HttpException::JSON_RESPONSE);
        } 
    }

    public function update(): Response
    {
        $fileUrl = '';
        $alt = '';
        
        try {
            $fileUrl = $this->getRequest()->json('fileUrl', '');
            $altText = $this->getRequest()->json('altText', '');

            $updateResult = $this->mediaService->update($fileUrl, $altText);
            if ($updateResult) {
                return $this->renderJson('Файл успешно обновлен!');
            } else {
                return $this->errorResponseFactory->createJsonError('Объект не найден или уже удален', 200);
            }
        } catch (MediaException $e) {
            // Обработка ошибок, связанных только с обновлением медиа
            Logger::error('AdminMediaApiController.update Ошибка при загрузке картинки.', ['fileUrl' => $fileUrl, 'alt' => $alt], $e);
            throw new HttpException('Ошибка при обновлении: ' . $e->getMessage(), 400, $e, HttpException::JSON_RESPONSE);
        } catch (PDOException $e) {
            // Удаляем файл, если не удалось сохранить в БД
            Logger::error("AdminMediaApiController.update Ошибка при сохранении в БД", ['fileUrl' => $fileUrl, 'alt' => $alt], $e);
            throw new HttpException('Ошибка при обновлении данных.', 500, $e, HttpException::JSON_RESPONSE);
        } catch (Throwable $e) {
            // Логируем ошибку и возвращаем ответ
            Logger::error("AdminMediaApiController.update Сбой при обновлении картинки", ['fileUrl' => $fileUrl, 'alt' => $alt], $e);
            throw new HttpException('Произошел сбой при обновлении картинки.', 500, $e, HttpException::JSON_RESPONSE);
        } 
    }

    public function delete(): Response
    {
        $fileUrl = '';
        
        try {
            $fileUrl = $this->getRequest()->json('fileUrl', '');

            $deleteResult = $this->mediaService->delete($fileUrl);
            
            if ($deleteResult) {
                return $this->renderJson('Файл успешно удален!');
            } else {
                return $this->errorResponseFactory->createJsonError('Объект не найден или уже удален', 200);
            }
        } catch (MediaException $e) {
            // Обработка ошибок, связанных только с загрузкой медиа
            Logger::error('AdminMediaApiController.delete Ошибка при удалении картинки.', ['fileUrl' => $fileUrl], $e);
            throw new HttpException('Ошибка при удалении: ' . $e->getMessage(), 400, $e, HttpException::JSON_RESPONSE);
        } catch (PDOException $e) {
            // Удаляем файл, если не удалось сохранить в БД
            Logger::error("AdminMediaApiController.delete Ошибка при удалении в БД", ['fileUrl' => $fileUrl], $e);
            throw new HttpException('Ошибка при удалении данных.', 500, $e, HttpException::JSON_RESPONSE);
        } catch (Throwable $e) {
            // Логируем ошибку и возвращаем ответ
            Logger::error("AdminMediaApiController.delete Сбой при удалении файла", ['fileUrl' => $fileUrl], $e);
            throw new HttpException('Произошел сбой при удалении файла.', 500, $e, HttpException::JSON_RESPONSE);
        } 
    }
}

