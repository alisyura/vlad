<?php
// app/exceptions/UserDataException.php

class UserDataException extends Exception
{
    private array $errors = [];

    /**
     * Конструктор класса SessionException.
     * @param string $message Сообщение об ошибке (которое покажем пользователю).
     * @param array $errors Массив ошибок пользовательских данных.
     * @param int $code Код ошибки
     * @param Throwable|null $previous Предыдущее исключение, если есть.
     */
    public function __construct(string $message, array $errors = [], int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getValidationErrors(): array
    {
        return $this->errors;
    }
}