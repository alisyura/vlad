<?php
// app/exceptions/SubmissionException.php

class SubmissionException extends Exception
{
    // Здесь можно добавить пользовательский код для ошибок,
    // если нужно различать их без использования сообщений.
    const ADMIN_NOT_FOUND = 1;
    const CONTENT_EMPTY = 2;
    const IMAGE_SIZE_INCORRECT = 3;

    /**
     * Конструктор класса SubmissionException.
     * @param string $message Сообщение об ошибке.
     * @param int $code Код ошибки
     * @param Throwable|null $previous Предыдущее исключение, если есть.
     */
    public function __construct($message, $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}