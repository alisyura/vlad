<?php

namespace App\Http\Controllers\Integration;

use Response;
use App\Framework\Security\SecureRequest;
use App\Framework\Security\NonceStorageInterface;
use App\Framework\Security\SecureRequestFactory;
use Logger;
use Request;
use RuntimeException;

/**
 * @deprecated Данный контроллер находится в разработке. 
 * Имя и поведение могут кардинально измениться. 
 * Используйте на свой страх и риск.
 */
class EndpointServerApiController extends \BaseController
{
    use \App\Traits\DevelopmentWarning;

    private NonceStorageInterface $storage;
    private Request $request;
    private SecureRequestFactory $secureRequestFactory;
    private \PDO $db;

    public function __construct(\ResponseFactory $responseFactory, 
        NonceStorageInterface $storage, SecureRequestFactory $secureRequestFactory,
        Request $request, \PDO $db)
    {
        parent::__construct(null, null, $responseFactory);
        $this->storage = $storage;
        $this->request = $request;
        $this->secureRequestFactory = $secureRequestFactory;
        $this->db = $db;
    }
    
    public function process(): \Response
    {
        $clientLogin = $this->request->server('PHP_AUTH_USER', '');
        $secretKey = null;
        $secRequest = null;

        try {
            if (empty($clientLogin)) {
                throw new RuntimeException(
                    'Authentication required', 
                    401 // HTTP 401 Unauthorized
                );
            }

            $secRequest = $this->secureRequestFactory->createForLogin($clientLogin);

            $incomingData = $secRequest->getData();          
            // Валидация обязательных полей
            if (empty($incomingData['order_id'])) {
                throw new RuntimeException(
                    'Order ID is required', 
                    400 // HTTP 400 Bad Request
                );
            }

            // --- бизнес-логика здесь ---
            // Например:
            $orderId = (int)$incomingData['order_id'];
            $result = $this->processOrder($orderId, $incomingData);

            return $this->renderSecure([
                    'message' => "Заказ #$orderId обработан",
                    'received' => $incomingData,
                    'data' => $result
                ], $secRequest->getSecretKey());
        } catch (\Throwable $e) {
            // Если что-то пошло не так (взломали подпись или протухло время)
            // Отправляем подписанную ошибку

            Logger::error('Error during process server api request', 
                [
                    'clientLogin' => $clientLogin
                ], 
                $e);

            // Безопасное получение ключа
            $errorKey = $secretKey ?? 'default_error_key';
            if ($secRequest !== null && method_exists($secRequest, 'getSecretKey')) {
                $errorKey = $secRequest->getSecretKey();
            }

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

    private function processOrder(int $orderId, array $data): array
    {
        // бизнес-логика здесь
        // Например:
        // 1. Проверка существования заказа
        // 2. Обновление статуса
        // 3. Логирование
        // 4. etc.
        
        return [
            'processed_at' => date('Y-m-d H:i:s'),
            'order_id' => $orderId,
            'status' => 'processed'
        ];
    }
}
    