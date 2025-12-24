<?php

namespace App\Http\Controllers\Integration;

use Response;
use App\Framework\Security\SecureRequest;
use App\Framework\Security\FileNonceStorage;

/**
 * @deprecated Данный контроллер находится в разработке. 
 * Имя и поведение могут кардинально измениться. 
 * Используйте на свой страх и риск.
 */
class EndpointServerApiController extends \BaseController
{
    use \App\Traits\DevelopmentWarning;

    public function __construct(\ResponseFactory $responseFactory)
    {
        parent::__construct(null, null, $responseFactory);
    }
    
    public function process(): \Response
    {
        $secretKey = \Config::get('security.APP_SECRET_KEY');
        $maxDrift = \Config::get('security.MaxDriftSeconds'); // Максимальное время жизни запроса в секундах

        try {
            // создаем объект запроса. 
            // Если подпись неверна или время вышло — он сам выкинет Exception.
            $storage = new FileNonceStorage(\Config::get('security.NonceFilesDir'));
            $request = new SecureRequest($secretKey, $maxDrift, $storage);
            $incomingData = $request->getData();

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
}
    