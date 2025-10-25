<?php
//declare(strict_types=1);
/**
 * Класс SubmissionController отвечает за обработку AJAX-запросов,
 * связанных с обработкой пользовательский материалов.
 *
 * @property Request $request Объект HTTP-запроса.
 */
class SubmissionController extends BaseController
{
    use JsonResponseTrait;

    private SubmissionService $service;
    private LinkValidator $linkValidator;

    /**
     * Конструктор класса AjaxController.
     *
     * @param Request $request Объект запроса, внедряется через DI-контейнер.
     * @param SubmissionService $submissionService Сервис, содержащий бизнес-логику по обработке пользовательских материалов, внедряется через DI-контейнер.
     * @param LinkValidator $linkValidator Валидатор, содержащий логику по проверке ссылок, внедряется через DI-контейнер.
     */
    public function __construct(Request $request, SubmissionService $submissionService,
        LinkValidator $linkValidator)
    {
        parent::__construct($request, null);
        $this->service = $submissionService;
        $this->linkValidator = $linkValidator;
    }

    public function publish()
    {
        $content = $this->request->post('text') ?? '';
        $videoLink = $this->request->post('video_link') ?? '';
        $file = $this->request->file('image') ?? null;

        try {
            if (!empty($videoLink) && !$this->linkValidator->isValidSingleVideoLink($videoLink)) {
                throw new SubmissionException('Ссылка неверного формата или домен не разрешен.');
            }
            $this->service->handleSubmission(Config::get('admin.AdminRoleName'),
                $content, $videoLink, $file);

            $this->sendSuccessJsonResponse('Материал успешно отправлен на модерацию');
        } catch (SubmissionException $e) {
            Logger::error("Ошибка при добавлении пользовательского материала.", [], $e);
            $this->sendErrorJsonResponse($e->getMessage(), 400);
        } catch (Throwable $e) {
            Logger::error("Сбой при добавлении пользовательского материала", [], $e);
            $this->sendErrorJsonResponse('Произошла ошибка при добавлении материала.', 500);
        }

        exit();
    }
}