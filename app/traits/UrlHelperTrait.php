<?php

// app/traits/UrlHelperTrait.php

/**
 * Trait для вспомогательных операций с URL.
 *
 * Предоставляет переиспользуемый метод для проверки наличия подстроки 'thrash' в URL-адресе.
 */
trait UrlHelperTrait
{
    /**
     * Проверяет, содержит ли строка 'thrash'.
     *
     * Метод использует нативную функцию str_contains() для PHP 8+ для лучшей производительности
     * и читаемости. Для версий PHP ниже 8 он возвращается к использованию strpos() для обеспечения
     * обратной совместимости.
     *
     * @param string $haystack Строка (обычно URL-путь), в которой выполняется поиск.
     * @return bool Возвращает true, если подстрока 'thrash' найдена, иначе false.
     */
    protected function hasThrash(string $haystack): bool
    {
        // Проверка на наличие str_contains() для совместимости с PHP 8+
        if (function_exists('str_contains')) {
            return str_contains($haystack, 'thrash');
        }
        
        // Резервный метод для старых версий PHP
        return strpos($haystack, 'thrash') !== false;
    }
}