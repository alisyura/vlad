<?php

// app/core/View.php
/**
 * Class View
 *
 * Реализует интерфейс ViewInterface и отвечает за рендеринг представлений (шаблонов)
 * и макетов (лейаутов).
 * Класс управляет путями к корневой директории представлений и путями к основным макетам.
 */
final class View implements ViewInterface
{
    /**
     * @var string Корневой путь к директории с файлами представлений.
     */
    private string $viewsRootPath;

    /**
     * @var string Путь к файлу макета для страницы входа (логина).
     */
    private string $loginLayoutPath;

    /**
     * @var string Путь к файлу макета для административной части.
     */
    private string $adminLayoutPath;

    /**
     * @var string Путь к файлу макета для клиентской (публичной) части.
     */
    private string $clientLayoutPath;

    /**
     * Конструктор класса View.
     *
     * Инициализирует пути к корневой директории представлений и основным макетам.
     *
     * @param string $viewsRootPath Путь к корневой директории представлений (например, 'views').
     * @param string $loginLayoutPath Относительный путь к макету логина (например, 'layouts/login.phtml').
     * @param string $adminLayoutPath Относительный путь к макету административной части (например, 'layouts/admin.phtml').
     * @param string $clientLayoutPath Относительный путь к макету клиентской части (например, 'layouts/client.phtml').
     */
    public function __construct(string $viewsRootPath, 
        string $loginLayoutPath, string $adminLayoutPath, string $clientLayoutPath)
    {
        $this->viewsRootPath = $viewsRootPath;
        $this->loginLayoutPath = $loginLayoutPath;
        $this->adminLayoutPath = $adminLayoutPath;
        $this->clientLayoutPath = $clientLayoutPath;
    }
    
    /**
     * Возвращает корневой путь к директории с файлами представлений.
     *
     * @return string Корневой путь представлений.
     */
    public function getViewsRootPath()
    {
        return $this->viewsRootPath;
    }
    
    /**
     * Рендерит указанный шаблон и возвращает его содержимое в виде строки.
     *
     * Данный метод устанавливает HTTP-код ответа, отправляет указанные заголовки
     * и рендерит шаблон, используя буферизацию вывода.
     *
     * @param string $templatePath Относительный путь к файлу шаблона (например, 'users/profile.phtml').
     * @param array $data Ассоциативный массив данных, которые будут доступны в шаблоне.
     * @param array $headers Массив HTTP-заголовков для отправки (например, ['Location: /']).
     * @param int $httpCode HTTP-код ответа (по умолчанию 200 OK).
     * @param bool $replace Флаг, указывающий, следует ли заменить предыдущий заголовок с тем же именем (по умолчанию true).
     * @return string Содержимое отрендеренного шаблона.
     */
    public function render(string $templatePath, array $data = [], array $headers = [], 
        $httpCode = 200, $replace = true): string
    {
        // Устанавливаем HTTP-код ответа
        http_response_code($httpCode);

        // Устанавливаем заголовок Content-Type по умолчанию, если он не был передан
        $contentTypeSet = false;
        foreach ($headers as $curHeader) {
            if (!empty($curHeader)) {
                if (stripos($curHeader, 'Content-Type') === 0) {
                    $contentTypeSet = true;
                }
                header($curHeader, $replace);
            }
        }
        if (!$contentTypeSet) {
            header('Content-Type: text/html; charset=utf-8');
        }
        extract($data);
        ob_start();
        include $this->viewsRootPath . '/' . $templatePath;
        return ob_get_clean();
    }

    /**
     * Рендерит всю страницу, включая основное содержимое и макет.
     *
     * Если $contentTemplatePath указан, он рендерится первым. Затем рендерится
     * файл макета, в котором уже доступна переменная $content (если она была отрендерена).
     * Вывод сразу отправляется в браузер.
     *
     * @param string|null $contentTemplatePath Относительный путь к шаблону содержимого,
     * или null, если рендерится только макет (например, для страницы логина).
     * @param string $layoutTemplatePath Относительный путь к файлу макета.
     * @param array $data Ассоциативный массив данных, которые будут доступны в макете и, если он указан, в содержимом.
     * @param array $headers Массив HTTP-заголовков для отправки (применяются только при рендеринге содержимого).
     * @return void
     */
    private function renderWithLayout(?string $contentTemplatePath, 
                string $layoutTemplatePath, array $data = [], array $headers = []): void
    {
        if (!is_null($contentTemplatePath))
        {
            $content = $this->render($contentTemplatePath, $data, $headers);
        }
        $exportData = $data['export'] ?? [];
        if (empty($exportData)) {
            // для админки
            extract($data);
        }
        require $this->viewsRootPath . '/' . $layoutTemplatePath;
    }
    
    /**
     * Рендерит страницу входа (логина) с использованием макета для логина.
     *
     * Содержимое не рендерится, используется только макет логина.
     *
     * @param array $data Ассоциативный массив данных, доступных в макете.
     * @return void
     */
    public function renderLogin(array $data = []): void
    {
        $this->renderWithLayout(null, $this->loginLayoutPath, $data);
    }
    
    /**
     * Рендерит страницу административной части с использованием административного макета.
     *
     * @param string $contentView Относительный путь к шаблону основного содержимого страницы.
     * @param array $data Ассоциативный массив данных.
     * @param array $headers Массив HTTP-заголовков для отправки.
     * @return void
     */
    public function renderAdmin(string $contentView, array $data = [], array $headers = []): void
    {
        $this->renderWithLayout($contentView, $this->adminLayoutPath, $data, $headers);
    }

    /**
     * Рендерит страницу клиентской (публичной) части с использованием клиентского макета.
     *
     * @param string $contentView Относительный путь к шаблону основного содержимого страницы.
     * @param array $data Ассоциативный массив данных.
     * @param array $headers Массив HTTP-заголовков для отправки.
     * @return void
     */
    public function renderClient(string $contentView, array $data = [], array $headers = []): void
    {
        $this->renderWithLayout($contentView, $this->clientLayoutPath, $data, $headers);
    }
}