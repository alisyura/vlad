<?php

namespace App\Http\Controllers\Integration;

use Response;
use App\Framework\Security\SecureRequest;
use App\Framework\Security\NonceStorageInterface;
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
    private \PDO $db;

    public function __construct(\ResponseFactory $responseFactory, 
        NonceStorageInterface $storage,
        Request $request, \PDO $db)
    {
        parent::__construct(null, null, $responseFactory);
        $this->storage = $storage;
        $this->request = $request;
        $this->db = $db;
    }
    
    public function process(): \Response
    {
        $maxDrift = \Config::get('security.MaxDriftSeconds'); // Максимальное время жизни запроса в секундах

        try {
            // создаем объект запроса. 
            // Если подпись неверна или время вышло — он сам выкинет Exception.
            $clientLogin = $this->request->server('PHP_AUTH_USER', '');
            if (!empty($clientLogin))
            {
                throw new RuntimeException('Client user name empty');
            }
            $secretKey = $this->getSecretKeyByLogin($clientLogin);
            $secRequest = new SecureRequest($secretKey, $maxDrift, $this->storage);
            $incomingData = $secRequest->getData();
            

            // --- бизнес-логика здесь ---
            // Например:
            $orderId = $incomingData['order_id'] ?? 0;

            return $this->renderSecure([
                    'payload' => [
                        'message' => "Заказ #$orderId обработан",
                        'received' => $incomingData
                    ]
                ], $secretKey);
        } catch (\Throwable $e) {
            // Если что-то пошло не так (взломали подпись или протухло время)
            // Отправляем подписанную ошибку

            return $this->renderSecure([
                    'payload' => [
                        'error' => $e->getMessage()
                    ]
                ], $secretKey, $e->getCode() ?: 500);
        }
    }

    private function getSecretKeyByLogin($login): string
    {
        return \Config::get('security.APP_SECRET_KEY');
    }
}
    