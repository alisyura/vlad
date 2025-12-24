<?php

namespace App\Http\Controllers\Integration;

use GuzzleHttp\Client;
use App\Framework\Security\SimpleSecureClient;
use Response;

/**
 * @deprecated Данный контроллер находится в разработке. 
 * Имя и поведение могут кардинально измениться. 
 * Используйте на свой страх и риск.
 */
class GuzzleClientController extends \BaseController
{
    use \App\Traits\DevelopmentWarning;

    public function __construct(\ResponseFactory $responseFactory)
    {
        parent::__construct(null, null, $responseFactory);
    }

    public function callApi(): Response
    {

        $secretKey = \Config::get('security.APP_SECRET_KEY');

        // 1. Инициализация HTTP-клиента
        $guzzleClient = new Client([
            // Дополнительные настройки Guzzle, если нужны
        ]); 

        // 2. Инициализация вашего Secure Client
        $client = new SimpleSecureClient(
            $guzzleClient,
            'http://vlad.local/api/endpoint',
            'логин',
            'пароль',
            $secretKey
        );

        // временный вывод сообщения в браузер
        $outText = '';

        try {
            $result = $client->send([
                'order_id' => 123,
                'amount' => 500.00
            ], 'create_payment');

            $outText = "Статус HTTP: " . $result['status'] . "\n";
            $outText .= print_r($result['response'], true);

            if ($result['status'] === 200 && ($result['success'] ?? false)) {
                $outText .= "Ура! API подтвердило получение данных.";
            } else {
                $outText .= "Сервер ответил, но что-то пошло не так.";
            }

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Ошибка 4xx (например, 401 Unauthorized, 404 Not Found)
            $outText = "Ошибка клиента API: " . $e->getResponse()->getStatusCode() . " - " . $e->getMessage() . "\n";
        } catch (\Throwable $e) {
            // Другие ошибки (сеть, Nonce, JSON-кодирование/декодирование)
            $outText = "Произошла критическая ошибка: " . $e->getMessage() . "\n";
        }

        return $this->renderText($outText);
    }
}