<?php
// app/services/PaginationService.php

class PaginationService
{
    /**
     * Вычисляет все параметры, необходимые для пагинации.
     *
     * @param int $qntyPerPage Количество элементов на одной странице.
     * @param int $currentPage Номер текущей страницы.
     * @param int $totalQnty Общее количество всех элементов.
     * @param string $adminRoute Базовый маршрут административной панели, например 'admin'.
     * @param string $basePageSuffix Суффикс URL для страницы пагинации (например, 'posts' или 'tags').
     * @return array Массив, содержащий параметры пагинации:
     * - 'totalPages' (int): Общее количество страниц.
     * - 'offset' (int): Смещение для SQL-запроса (сколько записей пропустить).
     * - 'basePageUrl' (string): Базовый URL для генерации ссылок пагинации.
     * - 'paginationLinks' (array): Массив сгенерированных ссылок для "умной" пагинации.
     */
    public function calculatePaginationParams(int $qntyPerPage, int $currentPage, 
        int $totalQnty, string $adminRoute, string $basePageSuffix): array
    {
        // Определяем параметры пагинации
        // $tagsPerPage = Config::get('admin.TagsPerPage'); // Количество постов на страницу
        $currentPage = max(1, (int)$currentPage); // Убеждаемся, что страница не меньше 1
        $offset = ($currentPage - 1) * $qntyPerPage; // Вычисляем смещение

        // Вычисляем общее количество страниц
        $totalPages = ceil($totalQnty / $qntyPerPage);
        
        // Убеждаемся, что текущая страница не превышает общее количество
        $currentPage = min($currentPage, $totalPages);
        $offset = ($currentPage - 1) * $qntyPerPage;

        // Генерируем массив ссылок для умной пагинации
        // Базовый URL для админки
        $basePageUrl = '/' . htmlspecialchars($adminRoute) . '/' . $basePageSuffix;
        $paginationLinks = generateSmartPaginationLinks($currentPage, $totalPages, $basePageUrl);

        return [
            'totalPages' => $totalPages,
            'offset' => $offset,
            'basePageUrl' => $basePageUrl,
            'paginationLinks' => $paginationLinks
        ];
    }
}