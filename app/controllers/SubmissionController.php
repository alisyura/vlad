<?php
//declare(strict_types=1);
/**
 * Класс SubmissionController отвечает за обработку AJAX-запросов,
 * связанных с обработкой пользовательский материалов.
 */
class SubmissionController extends BaseController
{
    private SubmissionService $service;
    private LinkValidator $linkValidator;

    /**
     * Конструктор класса AjaxController.
     *
     * @param Request $request Объект запроса, внедряется через DI-контейнер.
     * @param SubmissionService $submissionService Сервис, содержащий бизнес-логику по обработке пользовательских материалов, внедряется через DI-контейнер.
     * @param LinkValidator $linkValidator Валидатор, содержащий логику по проверке ссылок, внедряется через DI-контейнер.
     * @param ResponseFactory $responseFactory Фабрика для создания объектов Response, внедряемая через Dependency Injection.
     */
    public function __construct(Request $request, SubmissionService $submissionService,
        LinkValidator $linkValidator, ResponseFactory $responseFactory)
    {
        parent::__construct($request, null, $responseFactory);
        $this->service = $submissionService;
        $this->linkValidator = $linkValidator;
    }

    public function publish(): Response
    {
        $content = $this->getRequest()->post('text') ?? '';
        $videoLink = $this->getRequest()->post('video_link') ?? '';
        $file = $this->getRequest()->file('image') ?? null;

        try {
            if (!empty($videoLink) && !$this->linkValidator->isValidSingleVideoLink($videoLink)) {
                throw new SubmissionException('Ссылка неверного формата или домен не разрешен.');
            }
            $this->service->handleSubmission(Config::get('admin.AdminRoleName'),
                $content, $videoLink, $file);

            return $this->renderJson('Материал успешно отправлен на модерацию');
        } catch (SubmissionException $e) {
            Logger::error("Ошибка при добавлении пользовательского материала.", [], $e);
            throw new HttpException('Ошибка при добавлении пользовательского материала.', 400, $e, HttpException::JSON_RESPONSE);
        } catch (Throwable $e) {
            Logger::error("Сбой при добавлении пользовательского материала", [], $e);
            throw new HttpException('Сбой при добавлении материала.', 500, $e, HttpException::JSON_RESPONSE);
        }
    }
}