<?php

// app/framework/response/MarkdownResponse.php

/**
 * HTTP-ответ с содержимым в формате Markdown.
 *
 * Класс предназначен для генерации ответов, содержащих структурированный
 * контент в формате Markdown. Используется для:
 *   - llms.txt / llms-full.txt (описание сайта для AI-краулеров);
 *   - генерации документации на лету;
 *   - отдачи текстовых шаблонов с динамическими данными.
 *
 * В отличие от родительского TextResponse, данный класс устанавливает
 * корректный Content-Type: text/markdown, что является важным сигналом
 * для AI-краулеров (GPTBot, ClaudeBot, PerplexityBot) при обработке
 * llms.txt файлов согласно спецификации llmstxt.org.
 *
 * @extends TextResponse
 */
class MarkdownResponse extends TextResponse
{
     /**
     * Конструктор класса MarkdownResponse.
     *
     * Генерирует Markdown-контент на основе переданных данных и шаблона,
     * после чего передает его в родительский конструктор TextResponse.
     *
     * @param array $data Ассоциативный массив данных для шаблона.
     *                     Обязательный ключ: 'views_path' — путь к директории с шаблонами.
     *                     Дополнительные ключи определяются содержимым шаблона.
     * @param string $templateFile Имя файла шаблона Markdown (например, 'llms.md.php').
     *                              Файл должен находиться в директории, указанной в $data['views_path'].
     * @param string $url Базовый URL сайта. Используется для построения
     *                     абсолютных ссылок внутри Markdown-контента.
     * @param int $statusCode HTTP-код статуса ответа (по умолчанию 200).
     * @param array $headers Дополнительные HTTP-заголовки.
     */
    public function __construct(
        array $data, 
        string $templateFile,
        int $statusCode = 200, 
        array $headers = []
    ) {
        // Валидация данных перед генерацией
        $this->validateData($data, $templateFile);

        $markdownContent = $this->generateMarkdown(
            $data, 
            $templateFile
        );
        
        parent::__construct($markdownContent, $statusCode, $headers); 
    }

    /**
     * Возвращает массив стандартных HTTP-заголовков для Markdown-ответа.
     *
     * Переопределяет родительский метод, устанавливая корректный
     * Content-Type для Markdown-контента.
     *
     * @return array Ассоциативный массив заголовков:
     *               [
     *                   'Content-Type' => 'text/markdown; charset=utf-8',
     *               ]
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'text/markdown; charset=utf-8',
        ];
    }

    /**
     * Генерирует Markdown-контент на основе данных и шаблона.
     *
     * @param array $data Ассоциативный массив данных для шаблона.
     * @param string $templateFile Имя файла шаблона.
     * @return string Сгенерированный Markdown-контент.
     */
    private function generateMarkdown(array $data, string $templateFile): string
    {
        // Собираем полный путь к файлу шаблона
        $fullPath = $data['views_path'] . DIRECTORY_SEPARATOR . $templateFile;

        // Проверяем существование файла
        if (!file_exists($fullPath)) {
            throw new \RuntimeException(
                "Шаблон не найден: {$fullPath}"
            );
        }

        // Включаем буферизацию вывода
        ob_start();

        try {
            // Подключаем шаблон (он может использовать echo, print, HTML)
            include $fullPath;

            // Возвращаем содержимое буфера и очищаем его
            return ob_get_clean();

        } catch (\Throwable $e) {
            // Очищаем буфер в случае ошибки
            ob_end_clean();
            throw new \RuntimeException(
                "Ошибка при обработке markdown шаблона '{$templateFile}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Проверяет корректность переданных данных перед генерацией ответа.
     *
     * Выполняет следующие проверки:
     *   - $data не null и не пустой массив;
     *   - $data содержит обязательные ключи: 'views_path', 'posts', 'pages';
     *   - $data['views_path'] — непустая строка и директория существует;
     *   - $templateFile — непустая строка;
     *   - $url — непустая строка.
     *
     * @param array $data Данные для шаблона.
     * @param string $templateFile Имя файла шаблона.
     * @throws \InvalidArgumentException Если какая-либо проверка не пройдена.
     * @return void
     */
    private function validateData(array $data, string $templateFile): void
    {
        // Проверка: $data не пустой
        if (empty($data)) {
            throw new \InvalidArgumentException(
                'Массив Data не может быть пустым'
            );
        }

        // Проверка: обязательные ключи в $data
        $requiredKeys = ['views_path', 'base_url', 'post', 'page'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new \InvalidArgumentException(
                    "Обязательный ключ '{$key}' отсутствует в массиве data"
                );
            }
        }

        // Проверка: $data['views_path'] — непустая строка
        if (empty($data['views_path']) || !is_string($data['views_path'])) {
            throw new \InvalidArgumentException(
                'views_path не может быть пустой строкой'
            );
        }

        // Проверка: $data['base_url'] — непустая строка
        if (empty($data['base_url']) || !is_string($data['base_url'])) {
            throw new \InvalidArgumentException(
                'base_url не может быть пустой строкой'
            );
        }

        // Проверка: $data['views_path'] — существующая директория
        if (!is_dir($data['views_path'])) {
            throw new \InvalidArgumentException(
                "Базовый каталог Views не существует: {$data['views_path']}"
            );
        }

        // Проверка: $templateFile — непустая строка
        if (empty($templateFile) || !is_string($templateFile)) {
            throw new \InvalidArgumentException(
                'templateFile не может быть пустой строкой'
            );
        }
    }
}