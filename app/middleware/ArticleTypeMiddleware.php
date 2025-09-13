<?php
// app/middleware/ArticleTypeMiddleware.php

class ArticleTypeMiddleware implements MiddlewareInterface
{
    public function handle(?array $articleTypes = null): bool
    {
        // Если массив пустой или не передан, считаем, что проверка не пройдена
        if (empty($articleTypes)) {
            return false;
        }

        // Разрешенные типы постов
        $allowedTypes = ['post', 'page'];

        // Проверяем, что все элементы из $articleTypes присутствуют в $allowedTypes.
        // Используем array_diff для поиска неразрешенных элементов.
        $unallowedTypes = array_diff($articleTypes, $allowedTypes);
        
        // Если массив неразрешенных элементов пуст, значит, все переданные типы разрешены.
        return empty($unallowedTypes);
    }
}