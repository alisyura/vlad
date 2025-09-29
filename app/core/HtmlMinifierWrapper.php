<?php

// Используем библиотеку, которую вы установили ранее
use MatthiasMullie\Minify\HTML;

final class HtmlMinifierWrapper
{
    private bool $isEnabled;
    private $router; // Можно передать роутер или диспатчер

    public function __construct(bool $isEnabled, $router)
    {
        $this->isEnabled = $isEnabled;
        $this->router = $router;
    }

    /**
     * Запускает роутер и его логику, захватывает вывод и минифицирует его.
     */
    public function run(): void
    {
        // 1. Проверяем, нужно ли вообще минифицировать
        if (!$this->isEnabled) {
            $this->router->dispatch(); // Запускаем роутер без буферизации
            return;
        }

        // 2. Начинаем захват всего вывода приложения
        ob_start();
        
        // 3. Запуск роутера (вся логика, включая middleware и контроллеры)
        $this->router->dispatch(); 

        // 4. Получаем сгенерированный HTML
        $html = ob_get_clean();

        // 5. Минифицируем и выводим
        if (!empty($html)) {
            // Проверяем, является ли контент HTML. 
            // Это важно, чтобы не минифицировать, например, ответы API
            if ($this->isHtmlResponse()) {
                $minifier = new HTML($html);
                $minifiedHtml = $minifier->minify();
                echo $minifiedHtml;
            } else {
                // Если не HTML (например, JSON от /api/send_msg), выводим как есть
                echo $html; 
            }
        }
    }
    
    /**
     * Простая проверка заголовка Content-Type, если он был установлен.
     */
    private function isHtmlResponse(): bool
    {
        // Если заголовки были отправлены, проверяем Content-Type
        $headers = headers_list();
        foreach ($headers as $header) {
            if (stripos($header, 'Content-Type: text/html') !== false) {
                return true;
            }
            // Игнорируем минификацию для API-ответов (application/json)
            if (stripos($header, 'Content-Type: application/json') !== false) {
                return false;
            }
        }
        // Если заголовок не установлен, по умолчанию считаем HTML для главной логики
        return true; 
    }
}