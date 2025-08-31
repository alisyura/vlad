<?php
// app/exceptions/MediaException.php

class MediaException extends Exception
{
    // Здесь можно добавить пользовательский код для ошибок,
    // если вам нужно различать их без использования сообщений.
    const FILE_NOT_UPLOADED_OR_NOT_FOUND = 1;
    const UPLOAD_ERROR = 2;
    const INVALID_IMAGE_TYPE = 3;
    const FILE_SIZE_EXCEEDS_LIMIT = 4;
    const IMAGE_DIMENSIONS_DETECTION_ERROR = 5;
    const IMAGE_DIMENSIONS_TOO_SMALL = 6;
    const IMAGE_COULD_NOT_BE_OPENED = 7;
    const TEMP_FILE_CREATION_ERROR = 8;
    const DIRECTORY_CREATION_FAILED = 9;
    const FILE_SAVE_ERROR = 10;

    /**
     * Конструктор класса MediaException.
     * @param string $message Сообщение об ошибке.
     * @param int $code Код ошибки (используйте константы).
     * @param Throwable|null $previous Предыдущее исключение, если есть.
     */
    public function __construct($message, $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}