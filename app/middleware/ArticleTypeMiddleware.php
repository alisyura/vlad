<?php
// app/middleware/ArticleTypeMiddleware.php

class ArticleTypeMiddleware implements MiddlewareInterface
{
    public function handle(?array $articleTypes = null): bool
    {
        // Если массив пустой или не передан, считаем, что проверка не пройдена
        if (empty($articleTypes)) {
            $this->showError();
            return false;
        }

        // Разрешенные типы постов
        $allowedTypes = ['post', 'page'];

        // Проверяем, что все элементы из $articleTypes присутствуют в $allowedTypes.
        // Используем array_diff для поиска неразрешенных элементов.
        $unallowedTypes = array_diff($articleTypes, $allowedTypes);
        
        // Если массив неразрешенных элементов пуст, значит, все переданные типы разрешены.
        $res = empty($unallowedTypes);
        if (!$res)
        {
            $this->showError();
        }

        return $res;
    }

    private function showError()
    {
        header("HTTP/1.0 404 Not Found");
        $content = View::render('../app/views/admin/errors/not_found_view.php', [
            'adminRoute' => (new Request())->getAdminRoute(),
            'title' => '404'
        ]);
        require '../app/views/admin/admin_layout.php';
    }
}