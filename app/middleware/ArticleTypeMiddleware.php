<?php
// app/middleware/ArticleTypeMiddleware.php

/**
 * Посредник (Middleware), отвечающий за проверку типов статей.
 *
 * Этот класс-посредник гарантирует, что переданные типы статей (например, 'post' или 'page')
 * соответствуют списку разрешенных типов. Если переданы недопустимые значения,
 * выполнение прекращается и отображается страница ошибки.
 */
class ArticleTypeMiddleware implements MiddlewareInterface
{
    /**
     * Предоставляет метод для отображения страницы ошибки в админке.
     */
    use ShowAdminErrorViewTrait;

    /**
     * Обрабатывает запрос, проверяя, что переданные типы статей являются допустимыми.
     *
     * @param array|null $articleTypes Массив строк с типами статей для проверки.
     * @return bool Возвращает true, если все типы статей в массиве $articleTypes
     * являются допустимыми. Возвращает false и отображает ошибку в противном случае.
     */
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
     * Отображает страницу с ошибкой для административной панели.
     *
     * @deprecated Метод будет удален или заменен в будущих версиях.
     * Используйте альтернативные способы обработки ошибок.
     */
    private function showError()
    {
        $this->showAdminErrorView('Ошибка', 'Произошла непредвиденная ошибка.');
    }
}