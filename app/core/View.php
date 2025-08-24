<?php

class View
{
    /**
     * Рендерит конкретный шаблон и возвращает его содержимое в виде строки.
     *
     * @param string $template Путь к файлу шаблона.
     * @param array $data Данные для передачи в шаблон.
     * @return string Отформатированное содержимое HTML.
     */
    public static function render(string $template, array $data = []): string
    {
        extract($data);
        ob_start();
        include $template;
        return ob_get_clean();
    }

    /**
     * Рендерит всю страницу, включая основное содержимое и макет (layout).
     *
     * @param string $contentView Путь к файлу основного представления.
     * @param array $data Данные для передачи в представления.
     * @param string $layoutPath Путь к файлу макета.
     * @return void
     */
    public static function renderWithAdminLayout(string $contentView, 
        array $data = [], string $layoutPath = '../app/views/admin/admin_layout.php'): void
    {
        // Рендерим основное содержимое
        $content = self::render($contentView, $data);

        // Рендерим макет, который будет включать содержимое
        extract($data);
        require $layoutPath;
    }
}