<?php
// app/exceptions/HttpException.php

class HttpException extends Exception
{
    public const string HTML_RESPONSE = 'html';
    public const string JSON_RESPONSE = 'json';
    public const string XML_RESPONSE = 'xml';

    private string $responseType;

    /**
     * Конструктор класса HttpException.
     * @param string $message Сообщение об ошибке.
     * @param int $code Код ошибки. (По-умолчанию 400)
     * @param Throwable|null $previous Предыдущее исключение, если есть.
     * @param string $responseType Тип ожидаемого ответа клиентом. (По-умолчанию HttpException::HTML_RESPONSE)
     */
    public function __construct(string $message, ?int $code = 400, ?Throwable $previous = null, $responseType = HttpException::HTML_RESPONSE)
    {
        parent::__construct($message, $code, $previous);
        $this->responseType = $responseType;
    }

    public function getResponseType()
    {
        return $this->responseType;
    }
}