<?php

class RequestFactory
{
    private static $request;

    public static function createFromGlobals()
    {
        if (self::$request === null) {
            self::$request = new Request();
        }
        return self::$request;
    }
}