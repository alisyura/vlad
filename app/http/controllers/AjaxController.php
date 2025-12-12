<?php
//declare(strict_types=1);
/**
 * Класс AjaxController отвечает за обработку AJAX-запросов,
 * связанных с взаимодействием пользователей с контентом,
 * таким как голосование за посты.
 */
class AjaxController extends BaseController
{
    public function __construct(ResponseFactory $responseFactory)
    {
        parent::__construct(null, null, $responseFactory);
    }

    public function getCsrfToken(): Response
    {
        return $this->renderJson('', 200, ['csrf_token' => CSRF::getToken()]);
    }
}