<?php

// app/services/ErrorResponseFactory.php

/**
 * Фабрика для создания объектов Response, специализированная для ответов об ошибках (4xx, 5xx).
 *
 * Инкапсулирует логику рендеринга HTML-страниц ошибок и создания JSON-ответов об ошибках.
 */
class ErrorResponseFactory
{
    /**
     * Объект View, используемый для рендеринга HTML-шаблонов ошибок.
     *
     * @var View
     */
    private View $view;

    /**
     * Основная фабрика Response, используемая для создания конечных объектов ответа.
     *
     * @var ResponseFactory
     */
    private ResponseFactory $responseFactory;

    /**
     * Конструктор, инжектирующий необходимые зависимости.
     *
     * @param View $view Объект представления.
     * @param ResponseFactory $responseFactory Основная фабрика ответов.
     */
    public function __construct(View $view, ResponseFactory $responseFactory)
    {
        $this->view = $view;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Создает HTML-ответ для ошибок клиента или сервера (4xx, 5xx),
     * используя специализированный шаблон ошибок.
     *
     * Заменяет логику, ранее находившуюся в ShowClientErrorViewTrait.
     *
     * @param string $title Заголовок страницы ошибки (например, "Ошибка 404").
     * @param string $errMsg Сообщение об ошибке, видимое пользователю.
     * @param int $httpCode HTTP-код ответа (по умолчанию 500).
     * @return Response Объект готового HTML-ответа.
     */
    public function createClientError(string $title, string $errMsg, int $httpCode = 500): Response
    {
        // Используем новый чистый метод View
        $htmlContent = $this->view->renderClientContent(
            'errors/error_view.php', 
            [
                'title' => $title, 
                'error_message' => $errMsg, 
                'export' => [
                    'styles' => [
                        'error_view.css'
                    ],
                    'jss' => [
                    ]
                ]
            ]
        );

        return $this->responseFactory->createHtmlResponse($htmlContent, $httpCode);
    }
    
    /**
     * Создает JSON-ответ для ошибок API или AJAX-запросов.
     *
     * @param string|array $message Сообщение об ошибке или массив сообщений об ошибках.
     * @param int $statusCode HTTP-код ответа (по умолчанию 400 - Bad Request).
     * @param array $additionalData Дополнительные данные, которые могут быть полезны клиенту (например, поля валидации).
     * @return Response Объект готового JSON-ответа.
     */
    public function createJsonError(string|array $message, int $statusCode = 400, 
        array $additionalData = []): Response
    {
        $data = [
            'success' => false, 
            'message' => $message,
            'errors' => $additionalData ?? []
        ];
        return $this->responseFactory->createJsonResponse($data, $statusCode);
    }
}