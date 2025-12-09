<?php

// app/exceptions/CacheException.php

/**
 * Исключение, выбрасываемое, при ошибках с кэшем.
 */
class CacheException extends Exception
{
    protected $code = 500;
}