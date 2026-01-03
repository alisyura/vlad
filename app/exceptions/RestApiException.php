<?php

// app/exceptions/RestApiException.php

namespace App\Exception;

/**
 * Исключение, выбрасываемое, когда посетитель получает ошибки про голосовани.
 *
 * Это исключение помогает четко отделить эту бизнес-ошибку
 * от других возможных ошибок приложения.
 */
class RestApiException extends \Exception
{
    /**
     * Конструктор класса.
     *
     * @param string $message Сообщение об ошибке.
     * @param int $code Код ошибки.
     * @param ?Throwable $previous Предыдущее исключение в цепочке.
     */
    public function __construct(string $message, int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}