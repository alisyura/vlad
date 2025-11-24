<?php
// app/controllers/AdminSettingsApiController.php

class AdminSettingsApiController extends BaseAdminController
{
    private SettingsService $settingsService;
    private AuthService $authService;

    public function __construct(Request $request, SettingsService $settingsService, 
        ResponseFactory $responseFactory, AuthService $authService)
    {
        parent::__construct($request, null, $responseFactory);
        $this->settingsService = $settingsService;
        $this->authService = $authService;
    }

    public function delete(): Response
    {
        if (!$this->authService->isUserAdmin())
        {
            throw new HttpException('Не достаточно прав для выполнения этой операции', 403, null, HttpException::JSON_RESPONSE);
        }

        $postData=$this->getRequest()->getJson();

        // для логгирования в catch
        $idForLog = $postData['id'] ?? 'null';

        try {
            $hardDeleteResult = $this->settingsService->deleteSetting($postData['id']);

            if ($hardDeleteResult) {
                return $this->renderJson('Настройка успешно удалена.');
            } else {
                throw new HttpException('Произошла ошибка при удалении настройки.', 409, null, HttpException::JSON_RESPONSE);
            }
        } catch (InvalidArgumentException $e) {
            Logger::error("hardDelete. ошибки заполнены. выход", ['postId' => $idForLog], $e);
            throw new HttpException($e->getMessage(), $e->getCode(), $e, HttpException::JSON_RESPONSE);
        } catch (Throwable $e) {
            Logger::error("hardDelete. сбой при удалении поста/страницы", ['postId' => $idForLog], $e);
            throw new HttpException('Сбой при удалении поста/страницы.', 500, $e, HttpException::JSON_RESPONSE);
        }
    }
}