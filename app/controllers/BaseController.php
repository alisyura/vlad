<?php
// app/controllers/BaseController.php

/**
 * Абстрактный базовый класс для контроллеров, предоставляющий общие свойства
 * и методы для обработки запросов в приложении.
 *
 * @abstract
 */
abstract class BaseController {
    /**
     * Объект View для отображения view административной панели.
     *
     * @var ?View
     */
    protected ?View $view;
    /**
     * Объект Request для получения данных из запроса.
     *
     * @var ?Request
     */
    protected ?Request $request;

    /**
     * Фабрика для создания объектов Response.
     *
     * @var ?ResponseFactory
     */
    private ?ResponseFactory $responseFactory;

    /**
     * Конструктор контроллера, инжектирующий необходимые зависимости.
     *
     * @param ?Request $request Объект запроса.
     * @param ?View $view Объект представления (View).
     * @param ?ResponseFactory $responseFactory Фабрика для создания ответов.
     */
    public function __construct(?Request $request, ?View $view = null, 
        ?ResponseFactory $responseFactory = null)
    {
        $this->view = $view;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Возвращает объект View.
     * Используется для инкапсуляции доступа к зависимости.
     */
    protected function getView(): View {
        return $this->view;
    }

    /**
     * Возвращает объект Request.
     * Используется для инкапсуляции доступа к зависимости.
     */
    protected function getRequest(): Request {
        return $this->request;
    }

    /**
     * Вспомогательный метод для рендеринга шаблона и немедленного 
     * оборачивания результата в HtmlResponse.
     */
    protected function render(string $templatePath, array $data = [], 
        int $httpCode = 200): Response
    {
        $content = $this->getView()->renderClientContent($templatePath, $data); 
        return $this->responseFactory->createHtmlResponse($content, $httpCode);
    }
}
