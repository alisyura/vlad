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
     * Сервис для обработки файлов llms
     */
    private LlmsService $llmsService;

     /**
     * Конструктор класса LlmsController.
     *
     * @param Request $request Объект HTTP запроса, внедряемый через Dependency Injection.
     * @param View $view Объект представления, внедряемый через Dependency Injection.
     * @param ApplicationResponseFactory $responseFactory Фабрика для создания объектов Response, внедряемая через Dependency Injection.
     * @param SitemapService $sitemapService Сервис для получения и группировки данных для карты сайта, внедряемая через Dependency Injection.
     * @param SettingsService $settingsService Сервис для получения сео настроек, внедряемая через Dependency Injection.
     * @param LlmsService $llmsService Сервис для обработки файлов llms, внедряемая через Dependency Injection.
     */
    public function __construct(Request $request, View $view, 
        ApplicationResponseFactory $responseFactory, SitemapService $sitemapService,
        SettingsService $settingsService, LlmsService $llmsService) 
    {
        parent::__construct($request, $view, $responseFactory);
        $this->sitemapService = $sitemapService;
        $this->settingsService = $settingsService;
        $this->llmsService = $llmsService;
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
            $llmsTxtExists = $this->llmsService->checkFileExist(LlmsService::LLMS_TXT);

            // Файл не существует
            if (!$llmsTxtExists['success'])
            {
                $llmsDescriptionLength = Config::get('global.LlmsDescriptionLength');
                $llmsTxtExists = $this->llmsService->generateLlmsTxt($llmsDescriptionLength);
            }

            if (ob_get_level()) {
                ob_end_clean(); // Отключаем буферизацию
            }

            header('Content-Type: text/markdown; charset=utf-8');
            header('Content-Disposition: inline; filename="' . LlmsService::LLMS_TXT . '"');
            header('Content-Length: ' . $llmsTxtExists['fileSize']);
            
            // Отдаем файл потоково (readfile сам использует буферизацию)
            readfile($llmsTxtExists['filePath']);
            exit; // Важно остановить выполнение
        } catch (Throwable $e) {
            Logger::error("Error in showSitemap: ", [], $e);
            throw new HttpException('Ошибка получения списка постов для карты сайта', 500, $e);
        }
    }
}