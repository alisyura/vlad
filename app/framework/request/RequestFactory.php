<?php

// app/framework/request/RequestFactory.php

/**
 * Фабрика для создания и получения единственного экземпляра объекта Request.
 *
 * Класс использует паттерн Singleton для обеспечения того, чтобы
 * в приложении был только один объект Request, созданный из глобальных переменных.
 */
class RequestFactory
{
    /**
     * Статическое свойство для хранения единственного экземпляра объекта Request.
     *
     * @var Request|null
     */
    private static $request;

    /**
     * Создает (если еще не создан) и возвращает единственный экземпляр объекта Request,
     * заполненный данными из глобальных переменных PHP ($_GET, $_POST, $_SERVER и т.д.).
     *
     * @return Request Единственный экземпляр объекта Request.
     */
    public static function getInstance()
    {
        if (self::$request === null) {
            self::$request = new Request();
        }
        return self::$request;
    }
}