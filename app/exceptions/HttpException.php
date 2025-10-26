<?php
// app/exceptions/HttpException.php

class HttpException extends Exception
{
    /**
     * Конструктор класса HttpException.
     * @param string $message Сообщение об ошибке.
     * @param int $code Код ошибки
     * @param Throwable|null $previous Предыдущее исключение, если есть.
     */
    public function __construct($message, $code = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}