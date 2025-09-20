<?php
//declare(strict_types=1);

// app/controllers/VotingController.php

/**
 * Класс VotingController отвечает за обработку AJAX-запросов,
 * связанных с голосованием за посты.
 *
 * @property Request $request Объект HTTP-запроса.
 * @property VotingModel $model Модель для работы с данными готосования.
 */
class VotingController
{
    use JsonResponseTrait;
    use ShowClientErrorViewTrait;

    private $request;
    private VotingModel $model;
    private VotingService $service;

    /**
     * Конструктор класса AjaxController.
     *
     * @param Request $request Объект запроса, внедряется через DI-контейнер.
     * @param VotingService $votingService Сервис для обработки голосования, внедряется через DI-контейнер.
     * @param VotingModel $votingModel Модель для работы с данными голосования, внедряется через DI-контейнер.
     */
    public function __construct(Request $request, VotingService $votingService, VotingModel $votingModel)
    {
        $this->request = $request;
        $this->model = $votingModel;
        $this->service = $votingService;
    }

    public function getPostVotes()
    {
        $posts = $this->request->json('posts') ?? '';
        if (empty($posts)) {
            $this->sendErrorJsonResponse('Нет постов', 404);
            exit;
        }

        try {

            $uuid = getVisitorCookie();

            $visitorId=$this->model->getVisitorIdForUUID($uuid);

            // Убираем дубликаты и пустые значения
            $postUrls = array_unique(array_filter($posts));

            if (empty($postUrls)) {
                $this->sendErrorJsonResponse('Нет корректных постов', 422);
                exit;
            }

            $results = $this->model->findPostsByUrls($postUrls, $visitorId);

            $this->sendSuccessJsonResponse('Голоса получены', 200, ['votes' => $results]);

        } catch (Throwable $e) {
            Logger::error('getPostVotes. Ошибка получения голосов постов. '.$e->getTraceAsString());
            $this->sendErrorJsonResponse('Ошибка получения голосов постов.', 500);
            exit();
        }
    }

        /**
     * Обрабатывает голосование за пост через AJAX-запрос.
     *
     * Метод принимает данные из JSON-запроса, вызывает сервис для обработки
     * бизнес-логики и отправляет JSON-ответ об успехе или ошибке.
     *
     * @return void
     */
    public function reaction(): void
    {
        $postUrl = $this->request->json('postUrl') ?? '';
        $voteType = $this->request->json('type') ?? '';

        Logger::debug("reaction. postUrl=".$postUrl.", voteType=".$voteType);
        $uuid = getVisitorCookie();
        Logger::debug("reaction. uuid=".$uuid);

        try {
            $result = $this->service->handleVote($postUrl, $voteType, $uuid);

            $resJson = [
                'likes' => $result['likes_count'],
                'dislikes' => $result['dislikes_count']
            ];
            Logger::debug("reaction. resJson=".json_encode($resJson));
            $this->sendSuccessJsonResponse('Спасибо за ваш голос', 200, $resJson);
        } catch (ReactionException $e) {
            $errorJson = json_encode([
                'success' => false,
                'postUrl' => $postUrl,
                'type' => $voteType,
                'cookie' => $uuid,
                'uuid' => $uuid,
                'message' => $e->getMessage()
            ]);

            Logger::error("reaction. {$e->getMessage()}. res={$errorJson}", $e->getTraceAsString());

            $this->sendErrorJsonResponse($e->getMessage(), $e->getCode());
        } catch (Throwable $e) {
            $errorJson = json_encode([
                'success' => false,
                'postUrl' => $postUrl,
                'type' => $voteType,
                'cookie' => $uuid,
                'uuid' => $uuid,
                'message' => $e->getMessage()
            ]);

            Logger::error("reaction. Ошибка при голосовании. res={$errorJson}", $e->getTraceAsString());

            $this->sendErrorJsonResponse('Ошибка при регистрации голоса', 500);
        }
    }
}