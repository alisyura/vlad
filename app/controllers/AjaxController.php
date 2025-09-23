<?php
//declare(strict_types=1);
/**
 * Класс AjaxController отвечает за обработку AJAX-запросов,
 * связанных с взаимодействием пользователей с контентом,
 * таким как голосование за посты.
 */
class AjaxController
{
    use JsonResponseTrait;

    public function getCsrfToken()
    {
        $this->sendSuccessJsonResponse('', 200, ['csrf_token' => CSRF::getToken()]);
        exit;
    }
}