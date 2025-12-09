<?php
//declare(strict_types=1);

// app/controllers/TagsController.php

/**
 * Класс ContactController отвечает за обработку формы обратной связи.
 */
class ContactController extends BaseController
{
    private ContactFormValidator $validator;

    /**
     * Сервис для получения сео настроек
     */
    private SettingsService $settingsService;

    /**
     * Конструктор класса ContactController.
     *
     * @param Request $request Объект запроса, внедряется через DI-контейнер.
     * @param View $view Объект для отображения HTML шаблонов, внедряется через DI-контейнер.
     * @param ContactFormValidator $validator Валидатор, которые проверяет заполненные поля перед отпраывкой сообщения, внедряется через DI-контейнер.
     * @param ResponseFactory $responseFactory Фабрика для создания объектов Response, внедряемая через DI-контейнер.
     * @param SettingsService $settingsService Сервис для получения сео настроек, внедряемая через DI-контейнер.
     */
    public function __construct(Request $request, View $view, 
        ContactFormValidator $validator, ResponseFactory $responseFactory,
        SettingsService $settingsService)
    {
        parent::__construct($request, $view, $responseFactory);
        $this->validator = $validator;
        $this->settingsService = $settingsService;
    }

    /**
     * Отображает страницу "Контакты".
     *
     * Подготавливает данные для шаблона и отображает страницу контактов
     * с помощью объекта ViewAdmin. Обрабатывает возможные исключения.
     *
     * @return void
     */
    public function showKontakty(): Response {
        try {
            $URL = $this->getRequest()->getBaseUrl();

            $seoSettings = $this->settingsService->getMassSeoSettings([
                'index_page_title',
                'index_page_description',
                'index_page_keywords',
                'contact_title',
                'contact_keywords',
                'contact_description']);
            
            $title = $seoSettings['contact_title'];
            $keywords = $seoSettings['contact_keywords'] ?? $seoSettings['index_page_keywords'];
            $description = $seoSettings['contact_description'] ?? $seoSettings['index_page_description'];
            $title = is_array($title) ? $title['value'] : 'Обратная связь';
            $keywords = $keywords['value'];
            $description = $description['value'];

            $contentData = [
                'full_url' => $this->getRequest()->getRequestUrl(),
                'url_id' => 'kontakty',
                'export' => [
                    'page_type' => 'kontakty',
                    'title' => $title,
                    'site_name' => $seoSettings['index_page_title']['value'],
                    'keywords' => $keywords,
                    'description' => $description,
                    'url' => $URL,
                    'image' => $URL . asset('pic/logo.png'),
                    'robots' => 'noindex, follow',
                    'styles' => [
                        'kontakty.css'
                    ],
                    'jss' => [
                        'kontakty.js'
                    ]
                ]
            ];

            return $this->renderHtml('pages/kontakty.php', $contentData);
        } catch (Throwable $e) {
            Logger::error("Error in showKontakty: ", [], $e);
            throw new HttpException('Ошибка формы обратной связи.', 500, $e);
        }
        
    }

    /**
     * Отправляет сообщение из контактной формы.
     *
     * Выполняет валидацию данных, полученных из POST-запроса, и отправляет
     * сообщение с помощью сервиса ContactMailerService. Возвращает JSON-ответ
     * с результатом операции.
     *
     * @return void
     */
    public function sendMsg(): Response
    {
        try 
        {
            $data = [
                'name' => trim($this->getRequest()->post('name') ?? ''),
                'email' => trim($this->getRequest()->post('email') ?? ''),
                'title' => trim($this->getRequest()->post('title') ?? ''),
                'text' => trim($this->getRequest()->post('text') ?? '')
            ];
            $file = $this->getRequest()->file('image') ?? null;

            $errors = $this->validator->validate($data, $file);

            if (!empty($errors)) {
                throw new UserDataException('Ошибки при заполнении полей формы', $errors);
            }

            $mailer = new ContactMailerService();
            $result = $mailer->sendContactEmail($data, $file);

            if ($result['success']) {
                return $this->renderJson('Ваше сообщение успешно отправлено');
            } else {
                throw new HttpException('Ошибка при отправке сообщения', 400, null, 
                    HttpException::JSON_RESPONSE);
            }
        } catch(UserDataException $e) {
            Logger::error("sendMsg. " . $e->getMessage(), [], $e);
            throw new HttpException('При отправке сообщения произошла ошибка', 400, $e, HttpException::JSON_RESPONSE);
        } catch(Throwable $e) {
            Logger::error("sendMsg. Ошибка при отправке сообщения.", [], $e);
            throw new HttpException('При отправке сообщения произошла ошибка', 500, $e, HttpException::JSON_RESPONSE);
        }
    }
}