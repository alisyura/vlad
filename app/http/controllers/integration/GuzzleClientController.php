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

    public function signRequest(string $secretKey, array $data): string {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return CustomHMAC256::hmac_sha256_hex($secretKey, $json);
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



class CustomHMAC256 {
    // Константы SHA256
    private const K = [
        0x428a2f98, 0x71374491, 0xb5c0fbcf, 0xe9b5dba5,
        0x3956c25b, 0x59f111f1, 0x923f82a4, 0xab1c5ed5,
        0xd807aa98, 0x12835b01, 0x243185be, 0x550c7dc3,
        0x72be5d74, 0x80deb1fe, 0x9bdc06a7, 0xc19bf174,
        0xe49b69c1, 0xefbe4786, 0x0fc19dc6, 0x240ca1cc,
        0x2de92c6f, 0x4a7484aa, 0x5cb0a9dc, 0x76f988da,
        0x983e5152, 0xa831c66d, 0xb00327c8, 0xbf597fc7,
        0xc6e00bf3, 0xd5a79147, 0x06ca6351, 0x14292967,
        0x27b70a85, 0x2e1b2138, 0x4d2c6dfc, 0x53380d13,
        0x650a7354, 0x766a0abb, 0x81c2c92e, 0x92722c85,
        0xa2bfe8a1, 0xa81a664b, 0xc24b8b70, 0xc76c51a3,
        0xd192e819, 0xd6990624, 0xf40e3585, 0x106aa070,
        0x19a4c116, 0x1e376c08, 0x2748774c, 0x34b0bcb5,
        0x391c0cb3, 0x4ed8aa4a, 0x5b9cca4f, 0x682e6ff3,
        0x748f82ee, 0x78a5636f, 0x84c87814, 0x8cc70208,
        0x90befffa, 0xa4506ceb, 0xbef9a3f7, 0xc67178f2
    ];
    
    private const INITIAL_HASH = [
        0x6a09e667, 0xbb67ae85, 0x3c6ef372, 0xa54ff53a,
        0x510e527f, 0x9b05688c, 0x1f83d9ab, 0x5be0cd19
    ];
    
    private static function rightRotate(int $x, int $n): int {
        return ($x >> $n) | ($x << (32 - $n));
    }
    
    private static function sha256(string $data): string {
        $data = unpack('C*', $data); // Преобразуем в массив байт
        $originalBitLength = count($data) * 8;
        
        // Добавляем бит '1' (0x80)
        $data[] = 0x80;
        
        // Добавляем нули пока длина не станет ≡ 448 (mod 512)
        while ((count($data) * 8) % 512 !== 448) {
            $data[] = 0x00;
        }
        
        // Добавляем длину исходного сообщения (64 бита, big-endian)
        for ($i = 7; $i >= 0; $i--) {
            $data[] = ($originalBitLength >> ($i * 8)) & 0xFF;
        }
        
        $data = array_values($data);
        $h = self::INITIAL_HASH;
        
        // Обработка по 512-битным (64-байтным) блокам
        for ($chunkStart = 0; $chunkStart < count($data); $chunkStart += 64) {
            $w = array_fill(0, 64, 0);
            
            // Копируем первые 16 слов (32-битных)
            for ($i = 0; $i < 16; $i++) {
                $bytePos = $chunkStart + ($i * 4);
                $w[$i] = ($data[$bytePos] << 24) | 
                         ($data[$bytePos + 1] << 16) | 
                         ($data[$bytePos + 2] << 8) | 
                         $data[$bytePos + 3];
            }
            
            // Расширяем расписание
            for ($i = 16; $i < 64; $i++) {
                $s0 = self::rightRotate($w[$i - 15], 7) ^ 
                      self::rightRotate($w[$i - 15], 18) ^ 
                      ($w[$i - 15] >> 3);
                $s1 = self::rightRotate($w[$i - 2], 17) ^ 
                      self::rightRotate($w[$i - 2], 19) ^ 
                      ($w[$i - 2] >> 10);
                $w[$i] = ($w[$i - 16] + $s0 + $w[$i - 7] + $s1) & 0xFFFFFFFF;
            }
            
            // Инициализируем рабочие переменные
            list($a, $b, $c, $d, $e, $f, $g, $hh) = $h;
            
            // Основной цикл сжатия
            for ($i = 0; $i < 64; $i++) {
                $S1 = self::rightRotate($e, 6) ^ self::rightRotate($e, 11) ^ self::rightRotate($e, 25);
                $ch = ($e & $f) ^ ((~$e) & $g);
                $temp1 = ($hh + $S1 + $ch + self::K[$i] + $w[$i]) & 0xFFFFFFFF;
                $S0 = self::rightRotate($a, 2) ^ self::rightRotate($a, 13) ^ self::rightRotate($a, 22);
                $maj = ($a & $b) ^ ($a & $c) ^ ($b & $c);
                $temp2 = ($S0 + $maj) & 0xFFFFFFFF;
                
                $hh = $g;
                $g = $f;
                $f = $e;
                $e = ($d + $temp1) & 0xFFFFFFFF;
                $d = $c;
                $c = $b;
                $b = $a;
                $a = ($temp1 + $temp2) & 0xFFFFFFFF;
            }
            
            // Добавляем сжатый блок к текущему хешу
            $h[0] = ($h[0] + $a) & 0xFFFFFFFF;
            $h[1] = ($h[1] + $b) & 0xFFFFFFFF;
            $h[2] = ($h[2] + $c) & 0xFFFFFFFF;
            $h[3] = ($h[3] + $d) & 0xFFFFFFFF;
            $h[4] = ($h[4] + $e) & 0xFFFFFFFF;
            $h[5] = ($h[5] + $f) & 0xFFFFFFFF;
            $h[6] = ($h[6] + $g) & 0xFFFFFFFF;
            $h[7] = ($h[7] + $hh) & 0xFFFFFFFF;
        }
        
        // Формируем итоговый хеш
        $result = '';
        foreach ($h as $val) {
            $result .= sprintf('%08x', $val);
        }
        
        return hex2bin($result);
    }
    
    public static function hmac_sha256(string $key, string $message): string {
        $blockSize = 64; // Блок для SHA256
        
        // 1. Если ключ длиннее блока - хешируем его
        if (strlen($key) > $blockSize) {
            $key = self::sha256($key);
        }
        
        // 2. Добиваем ключ нулями до размера блока
        if (strlen($key) < $blockSize) {
            $key = str_pad($key, $blockSize, "\x00");
        }
        
        // 3. Создаем внутренний и внешний ключи
        $o_key_pad = $i_key_pad = '';
        for ($i = 0; $i < $blockSize; $i++) {
            $o_key_pad .= chr(ord($key[$i]) ^ 0x5C); // outer padded key
            $i_key_pad .= chr(ord($key[$i]) ^ 0x36); // inner padded key
        }
        
        // 4. Вычисляем внутренний хеш: H(i_key_pad || message)
        $inner_hash = self::sha256($i_key_pad . $message);
        
        // 5. Вычисляем итоговый хеш: H(o_key_pad || inner_hash)
        return self::sha256($o_key_pad . $inner_hash);
    }
    
    public static function hmac_sha256_hex(string $key, string $message): string {
        $binary = self::hmac_sha256($key, $message);
        return bin2hex($binary);
    }
}

// Или компактная версия, если нужна ТОЛЬКО HMAC (без общего SHA256):
class SimpleHMAC256 {
    public static function compute(string $secret, string $data): string {
        // Константы
        $ipad = str_repeat(chr(0x36), 64);
        $opad = str_repeat(chr(0x5C), 64);
        
        // Подготовка ключа
        if (strlen($secret) > 64) {
            $secret = self::rawSha256($secret);
        }
        $secret = str_pad($secret, 64, chr(0));
        
        // HMAC формула: H((K ⊕ opad) || H((K ⊕ ipad) || text))
        $k_ipad = $k_opad = '';
        for ($i = 0; $i < 64; $i++) {
            $k_ipad .= chr(ord($secret[$i]) ^ ord($ipad[$i]));
            $k_opad .= chr(ord($secret[$i]) ^ ord($opad[$i]));
        }
        
        $inner = self::rawSha256($k_ipad . $data);
        $result = self::rawSha256($k_opad . $inner);
        
        return bin2hex($result);
    }
    
    private static function rawSha256(string $data): string {
        // Упрощенная SHA256 (только для демонстрации)
        // На практике нужно реализовать полноценную SHA256 как выше
        return hash('sha256', $data, true); // Заглушка
    }
}

// Пример использования для вашего API
class APISigner {
    public static function signRequest(string $secretKey, array $data): string {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return CustomHMAC256::hmac_sha256_hex($secretKey, $json);
    }
    
    public static function verifyRequest(
        string $secretKey, 
        string $receivedSignature, 
        array $data
    ): bool {
        $expected = self::signRequest($secretKey, $data);
        // Безопасное сравнение (timing attack safe)
        return hash_equals($expected, $receivedSignature);
    }
}

// ТЕСТ
// echo "=== Тест HMAC-SHA256 на PHP (без встроенных функций) ===\n\n";

// $key = "my_secret_key";
// $message = '{"action":"get_data","id":123,"timestamp":' . time() . '}';

// $hmac = CustomHMAC256::hmac_sha256_hex($key, $message);
// echo "Ключ: $key\n";
// echo "Данные: $message\n";
// echo "HMAC-SHA256: $hmac\n";

// // Сравнение со встроенной функцией (для проверки)
// if (function_exists('hash_hmac')) {
//     $builtin = hash_hmac('sha256', $message, $key);
//     echo "Встроенная hash_hmac(): $builtin\n";
//     echo "Совпадает? " . (hash_equals($hmac, $builtin) ? "ДА" : "НЕТ") . "\n";
// }

// // Пример для вашего API
// echo "\n=== Пример для вашего API ===\n";
// $apiKey = "abcdef123456";
// $requestData = [
//     'method' => 'user.get',
//     'params' => ['id' => 42],
//     'nonce' => bin2hex(random_bytes(16)),
//     'timestamp' => time()
// ];

// $signature = APISigner::signRequest($apiKey, $requestData);
// echo "Подпись запроса: $signature\n";
// echo "Проверка: " . (APISigner::verifyRequest($apiKey, $signature, $requestData) ? "OK" : "FAIL") . "\n";