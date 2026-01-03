<?php

namespace App\Http\Controllers\Integration;

use App\Services\RestApiServerProcessorService;
use Response;
use Logger;
use Request;

/**
 * @deprecated Данный контроллер находится в разработке. 
 * Имя и поведение могут кардинально измениться. 
 * Используйте на свой страх и риск.
 */
class EndpointServerApiController extends \BaseController
{
    use \App\Traits\DevelopmentWarning;

    private RestApiServerProcessorService $service;

    public function __construct(\ResponseFactory $responseFactory,
        Request $request, RestApiServerProcessorService $service)
    {
        parent::__construct($request, null, $responseFactory);
        $this->service = $service;
    }
    
    public function process(): Response
    {
        $clientLogin = $this->getRequest()->server('PHP_AUTH_USER', '');
        $secretKey = null;

        try {
            $result = $this->service->processApiRequest($clientLogin);
            $secretKey = $result[RestApiServerProcessorService::SECRET_KET_COLUMN];
            unset($result[RestApiServerProcessorService::SECRET_KET_COLUMN]);

            return $this->renderSecure($result, $secretKey);
        } catch (\Throwable $e) {
            // Если что-то пошло не так (взломали подпись или протухло время)
            // Отправляем подписанную ошибку

            Logger::error('Error during process server api request', 
                [
                    'clientLogin' => $clientLogin
                ], 
                $e);

            // Безопасное получение ключа
            $errorKey = $secretKey ?? $this->service->getSecretKey();

            // Определяем HTTP статус
            $httpStatus = $e->getCode() >= 400 && $e->getCode() < 600 
                ? $e->getCode() 
                : 500;

            return $this->renderSecure([
                    'error' =>  $this->getSafeErrorMessage($e),
                    'error_code' => $this->getSafeErrorCode($e),
                ], $errorKey, $httpStatus);
        }
    }

    /**
     * Возвращает безопасный код ошибки
     */
    private function getSafeErrorCode(\Throwable $e): string
    {
        // Преобразуем имя класса в snake_case для клиента
        $className = get_class($e);
        $className = str_replace('\\', '_', $className);
        $className = preg_replace('/([a-z])([A-Z])/', '$1_$2', $className);
        
        return strtoupper($className);
    }

    /**
     * Возвращает безопасное сообщение об ошибке
     */
    private function getSafeErrorMessage(\Throwable $e): string
    {
        $message = $e->getMessage();
        
        // В продакшн режиме скрываем детали
        if (!\Config::isDev()) {
            return 'An error occurred while processing your request';
        }
        
        // В режиме разработки показываем больше информации
        return sprintf('%s: %s', get_class($e), $message);
    }
}
    