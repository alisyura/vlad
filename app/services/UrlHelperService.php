<?php

class UrlHelperService
{

    public function __construct()
    {
    }

    /**
     * Проверяет, содержит ли строка 'thrash' с помощью strpos().
     * Этот метод подходит для всех версий PHP.
     *
     * @param string $haystack Строка, в которой ищем.
     * @return bool
     */
    private function hasThrashLegacy(string $haystack): bool
    {
        // Важно: используем !== false, чтобы корректно обработать случай,
        // когда подстрока находится в самом начале (позиция 0).
        return strpos($haystack, 'thrash') !== false;
    }

    /**
     * Проверяет, содержит ли строка 'thrash' с помощью str_contains().
     * Этот метод доступен только в PHP 8 и выше.
     *
     * @param string $haystack Строка, в которой ищем.
     * @return bool
     */
    public function hasThrash(string $haystack): bool
    {
        // Простая и понятная функция, возвращающая true или false.
        if (function_exists('str_contains')) {
            return str_contains($haystack, 'thrash');
        }
        
        // Если версия PHP ниже 8, возвращаемся к старому методу.
        return $this->hasThrashLegacy($haystack);
    }
}