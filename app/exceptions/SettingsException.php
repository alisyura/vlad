<?php

// app/exceptions/SettingsException.php

/**
 * Исключение, выбрасываемое, при ошибках с настройками.
 */
class SettingsException extends Exception
{
    protected $code = 500;
}