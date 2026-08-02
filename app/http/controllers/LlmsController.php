<?php

// app/controllers/LlmsController.php

/**
 * Class LlmsController
 *
 * Контроллер для работы с llms файлами.
 */
class LlmsController extends BaseController {
    /**
     * @var SitemapService Объект сервиса для работы с данными карты сайта.
     */
    private SitemapService $sitemapService;

    /**
     * Сервис для получения сео настроек
     */
    private SettingsService $settingsService;

     /**
     * Конструктор класса LlmsController.
     *
     * @param Request $request Объект HTTP запроса, внедряемый через Dependency Injection.
     * @param View $view Объект представления, внедряемый через Dependency Injection.
     * @param ApplicationResponseFactory $responseFactory Фабрика для создания объектов Response, внедряемая через Dependency Injection.
     * @param SitemapService $sitemapService Сервис для получения и группировки данных для карты сайта, внедряемая через Dependency Injection.
     * @param SettingsService $settingsService Сервис для получения сео настроек, внедряемая через Dependency Injection.
     */
    public function __construct(Request $request, View $view, 
        ApplicationResponseFactory $responseFactory, SitemapService $sitemapService,
        SettingsService $settingsService) 
    {
        parent::__construct($request, $view, $responseFactory);
        $this->sitemapService = $sitemapService;
        $this->settingsService = $settingsService;
    }

    protected function getResponseFactory(): ApplicationResponseFactory {
        return parent::getResponseFactory();
    }

    /**
     * Отображает содержимое llms.txt файла.
     *
     * Метод получает все данные о постах и страницах и передает их в представление
     * для отображения в виде Markdown файла.
     *
     * @return Response
     */
    public function showLlms(): Response {
        try {
            $llmsDescriptionLength = Config::get('global.LlmsDescriptionLength');
            $result = $this->sitemapService->getSitemapData($llmsDescriptionLength, true, true);

            $seoSettings = $this->settingsService->getMassSeoSettings([
                'index_page_title',
                'index_page_description',
                'index_page_keywords']);

            $data = [
                'views_path' => $this->getView()->getViewsRootPath(),
                'base_url' => $this->getRequest()->getBaseUrl(),
                'title' => $seoSettings['index_page_title']['value'],
                'description' => $seoSettings['index_page_description']['value'],
                ...($result)
            ];
            return $this->getResponseFactory()->createMarkdownResponse(
                $data, 
                'llms.md.php'
            );
        } catch (Throwable $e) {
            Logger::error("Error in showSitemap: ", [], $e);
            throw new HttpException('Ошибка получения списка постов для карты сайта', 500, $e);
        }
    }
}