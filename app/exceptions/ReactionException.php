<?php

// app/exceptions/ReactionException.php

/**
 * Исключение, выбрасываемое, когда посетитель получает ошибки про голосовани.
 *
 * Это исключение помогает четко отделить эту бизнес-ошибку
 * от других возможных ошибок приложения.
 */
class ReactionException extends Exception
{
    /**
     * Конструктор класса.
     *
     * @param string $message Сообщение об ошибке.
     * @param int $code Код ошибки (по умолчанию 409 Conflict).
     * @param ?Throwable $previous Предыдущее исключение в цепочке.
     */
    public function __construct(string $message = "Вы уже голосовали за этот пост", int $code = 409, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}