<?php
// app/middleware/ArticleTypeMiddleware.php

class ArticleTypeMiddleware implements MiddlewareInterface
{
    use ShowAdminErrorViewTrait;

    private ViewAdmin $viewAdmin;

    public function __construct(ViewAdmin $viewAdmin)
    {
        $this->viewAdmin = $viewAdmin;
    }

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

    /**
     * @deprecated
     */
    private function showError()
    {
        $this->showAdminErrorView('Ошибка', 'Произошла непредвиденная ошибка.');
    }
}