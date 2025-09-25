<?php

// app/validators/LinkValidator.php

/**
 * Класс для валидации ссылок.
 *
 * Предоставляет методы для проверки URL-адресов на соответствие заданным правилам.
 */
class LinkValidator
{
    /**
     * Проверяет, является ли строка корректной ссылкой на видео с разрешенного сервиса.
     *
     * Метод валидирует, что строка является действующим URL-адресом и что
     * её домен входит в список разрешенных видеохостингов (YouTube, VK, RuTube и др.).
     *
     * @param string $link Ссылка для проверки.
     * @return bool Возвращает true, если ссылка действительна и находится на разрешенном домене,
     * в противном случае — false.
     */
    function isValidSingleVideoLink(string $link): bool
    {
        // 1. Проверяем, что строка является корректным URL.
        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            return false;
        }

        // 2. Определяем список разрешенных доменов.
        $allowedDomains = [
            'youtube.com',
            'www.youtube.com',
            'youtu.be',
            'vk.com',
            'vk.ru',
            'rutube.ru',
            'ok.ru',
            'mail.ru',
        ];

        // 3. Извлекаем домен из ссылки.
        $host = parse_url($link, PHP_URL_HOST);
        
        // 4. Проверяем, что домен существует и находится в списке.
        if ($host && in_array($host, $allowedDomains)) {
            return true;
        }

        return false;
    }
}