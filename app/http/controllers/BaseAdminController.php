<?php
// app/controllers/BaseAdminController.php

/**
 * Абстрактный базовый класс для контроллеров админки, предоставляющий общие свойства
 * и методы для обработки запросов в приложении.
 *
 * @abstract
 */
abstract class BaseAdminController extends BaseController {
    /**
     * Базовый маршрут (route) для доступа к административной панели.
     * Используется для формирования правильных URL.
     *
     * @var string
     */
    private $adminRoute;

    public function __construct(?Request $request, ?View $view = null, 
        ?ResponseFactory $responseFactory = null)
    {
        parent::__construct($request, $view, $responseFactory);
        $this->adminRoute = Config::get('admin.AdminRoute');
    }

    protected function getAdminRoute()
    {
        return $this->adminRoute;
    }

    /**
     * Вспомогательный метод для рендеринга шаблона страницы логина админки и немедленного 
     * оборачивания результата в HtmlResponse.
     */
    protected function renderLogin(array $data = [], int $httpCode = 200): Response
    {
        $view = $this->getView();
        if (null === $view)
        {
            throw new \RuntimeException('View is null');
        }
        $content = $view->renderLoginContent($data); 
        return $this->getResponseFactory()->createHtmlResponse($content, $httpCode);
    }

    /**
     * Вспомогательный метод для перенаправления и оборачивания результата в RedirectResponse.
     */
    protected function redirect(string $url, int $httpCode = 302): Response
    {
        return $this->getResponseFactory()->createRedirectResponse($url, $httpCode);
    }

    /**
     * Вспомогательный метод для рендеринга шаблона админки и немедленного 
     * оборачивания результата в HtmlResponse.
     */
    protected function renderHtml(string $templatePath, array $data = [], 
        int $httpCode = 200): Response
    {
        $view = $this->getView();
        if (null === $view)
        {
            throw new \RuntimeException('View is null');
        }
        $content = $view->renderAdminContent($templatePath, $data); 
        return $this->getResponseFactory()->createHtmlResponse($content, $httpCode);
    }
}
