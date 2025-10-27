<?php
//declare(strict_types=1);

// app/controllers/VotingController.php

/**
 * Класс VotingController отвечает за обработку AJAX-запросов,
 * связанных с голосованием за посты.
 */
class VotingController extends BaseController
{
    private VotingModel $model;
    private VotingService $service;

    /**
     * Конструктор класса AjaxController.
     *
     * @param Request $request Объект запроса, внедряется через DI-контейнер.
     * @param VotingService $votingService Сервис для обработки голосования, внедряется через DI-контейнер.
     * @param VotingModel $votingModel Модель для работы с данными голосования, внедряется через DI-контейнер.
     * @param ResponseFactory $responseFactory Фабрика для создания объектов Response, внедряемая через Dependency Injection.
     */
    public function __construct(Request $request, VotingService $votingService, 
        VotingModel $votingModel, ResponseFactory $responseFactory)
    {
        parent::__construct($request, null, $responseFactory);
        $this->model = $votingModel;
        $this->service = $votingService;
    }

    public function getPostVotes(): Response
    {
        $posts = $this->getRequest()->json('posts') ?? '';
        if (empty($posts)) {
            throw new HttpException('Нет постов', 404, null, HttpException::JSON_RESPONSE);
        }

        try {

            $uuid = getVisitorCookie();

            $visitorId=$this->model->getVisitorIdForUUID($uuid);

            // Убираем дубликаты и пустые значения
            $postUrls = array_unique(array_filter($posts));

            if (empty($postUrls)) {
                throw new HttpException('Нет корректных постов', 422, null, HttpException::JSON_RESPONSE);
            }

            $results = $this->model->findPostsByUrls($postUrls, $visitorId);

            return $this->renderJson('Голоса получены', 200, ['votes' => $results]);
        } catch (Throwable $e) {
            Logger::error('getPostVotes. Ошибка получения голосов постов. ', [], $e);
            if ($e instanceof HttpException)
            {
                throw $e;
            }
            throw new HttpException('Ошибка получения голосов постов.', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    /**
     * Обрабатывает голосование за пост через AJAX-запрос.
     *
     * Метод принимает данные из JSON-запроса, вызывает сервис для обработки
     * бизнес-логики и отправляет JSON-ответ об успехе или ошибке.
     *
     * @return Response
     */
    public function reaction(): Response
    {
        $postUrl = $this->getRequest()->json('postUrl') ?? '';
        $voteType = $this->getRequest()->json('type') ?? '';

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

            return $this->renderJson('Спасибо за ваш голос', 200, $resJson);
        } catch (ReactionException $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e, HttpException::JSON_RESPONSE);
        } catch (Throwable $e) {
            Logger::error("reaction. Ошибка при голосовании", 
                [ 
                    'success' => false,
                    'postUrl' => $postUrl,
                    'type' => $voteType,
                    'cookie' => $uuid,
                    'uuid' => $uuid,
                    'message' => $e->getMessage()
                ], $e);

            throw new HttpException('Ошибка при регистрации голоса', 500, $e, HttpException::JSON_RESPONSE);
        }
    }
}