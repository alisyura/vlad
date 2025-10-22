<?php
// app/exceptions/TagsException.php

class TagsException extends Exception
{
    /**
     * Конструктор класса TagsException.
     * @param string $message Сообщение об ошибке.
     * @param int $code Код ошибки
     * @param Throwable|null $previous Предыдущее исключение, если есть.
     */
    public function __construct($message, $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}