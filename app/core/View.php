<?php
/**
 * @deprecated
 */
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
    private static function renderBaseLayout(?string $contentView, 
        array $data = [], string $layoutPath): void
    {
        if (!is_null($contentView))
        {
            $content = self::render($contentView, $data);
        }
        extract($data);
        require $layoutPath;
    }

    public static function renderLogin(array $data = []): void
    {
        self::renderBaseLayout(null, $data, '../app/views/admin/login.php');
    }

    public static function renderAdmin(string $contentView, array $data = []): void
    {
        self::renderBaseLayout($contentView, $data, '../app/views/admin/admin_layout.php');
    }

    public static function renderPublic(string $contentView, array $data = []): void
    {
        self::renderBaseLayout($contentView, $data, '../app/views/public/main_layout.php');
    }
}