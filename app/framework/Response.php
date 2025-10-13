<?php

// app/core/Response.php

/**
 * Класс, представляющий HTTP-ответ.
 */
class Response
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
        echo $this->content;
    }

    /**
     * Устанавливает перенаправление (заголовок Location).
     *
     * @param string $url URL, на который нужно перенаправить.
     * @param int $statusCode Код статуса перенаправления (302 по умолчанию).
     * @return self
     */
    public function redirect(string $url, int $statusCode = 302): self
    {
        // 1. Устанавливаем статус перенаправления
        $this->setStatusCode($statusCode); 
        
        // 2. Устанавливаем заголовок Location
        $this->addHeader('Location', $url);
        
        // 3. Очищаем контент (перенаправление не должно иметь тела)
        $this->setContent('');
        
        return $this;
    }

    /**
     * Устанавливает ответ как JSON.
     *
     * @param array $data Данные для кодирования в JSON.
     * @param int $statusCode HTTP-код статуса.
     * @return self
     */
    public function json(array $data, int $statusCode = 200): self
    {
        $this->setStatusCode($statusCode);
        
        // Устанавливаем заголовок Content-Type
        $this->addHeader('Content-Type', 'application/json; charset=UTF-8');
        
        // Кодируем данные в строку JSON
        $this->setContent(json_encode($data)); 
        
        return $this;
    }
}