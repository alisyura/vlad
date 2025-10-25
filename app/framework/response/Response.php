<?php

// app/framework/response/Response.php

/**
 * Базовый класс, представляющий HTTP-ответ. 
 * Должен наследоваться для создания конкретных типов ответов (HTML, JSON, Redirect).
 */
abstract class Response
{
    /**
     * @var int HTTP-код статуса (например, 200, 404, 302).
     */
    protected int $statusCode = 200;

    /**
     * @var string Тело ответа (HTML, JSON, текст).
     */
    protected string $content = '';

    /**
     * @var array HTTP-заголовки.
     */
    protected array $headers = [];

    /**
     * Конструктор
     *
     * @param string $content Тело ответа.
     * @param int $statusCode HTTP-код статуса.
     * @param array $headers Дополнительные заголовки.
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = array_merge($this->getDefaultHeaders(), $headers);
    }

    /**
     * Устанавливает заголовки по умолчанию (например, Content-Type).
     *
     * @return array
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'text/html; charset=UTF-8',
        ];
    }
    
    // --- Методы-сеттеры и геттеры ---

    /**
     * Устанавливает HTTP-код статуса.
     *
     * @param int $statusCode
     * @return self
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Устанавливает тело ответа.
     *
     * @param string $content
     * @return self
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Добавляет или заменяет HTTP-заголовок.
     *
     * @param string $key
     * @param string $value
     * @return self
     */
    public function addHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    // --- Главный метод отправки ---
    
    /**
     * Отправляет HTTP-заголовки и тело ответа клиенту.
     */
    public function send(): void
    {
        // 1. Отправка заголовков
        $this->sendHeaders();
        
        // 2. Отправка тела ответа
        $this->sendContent();
    }

    /**
     * Отправляет заголовки, включая статусную строку.
     */
    protected function sendHeaders(): void
    {
        // Проверяем, что заголовки еще не были отправлены
        if (headers_sent()) {
            return;
        }

        // Отправляем статусную строку
        http_response_code($this->statusCode);

        // Отправляем остальные заголовки
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}", true);
        }
    }

    /**
     * Отправляет тело ответа.
     */
    protected function sendContent(): void
    {
        // Здесь можно добавить проверку, нужно ли отправлять контент 
        // для ответов с кодами вроде 204 No Content, 304 Not Modified
        if (in_array($this->statusCode, [204, 304])) {
            return;
        }
        
        echo $this->content;
    }
}