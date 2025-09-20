<?php

// app/services/VotingService.php

/**
 * Класс VotingService инкапсулирует бизнес-логику, связанную
 * с голосованием за посты.
 *
 * Сервис координирует операции с базой данных, управляет транзакциями
 * и отвечает за бизнес-правила (например, проверка повторного голоса).
 */
class VotingService
{
    private VotingModel $votingModel;
    private PDO $db;

    /**
     * Конструктор класса ReactionService.
     *
     * @param VotingModel $postAjaxModel Модель для работы с данными постов.
     * @param PDO $pdo Объект PDO для управления транзакциями.
     */
    public function __construct(VotingModel $votingModel, PDO $pdo)
    {
        $this->votingModel = $votingModel;
        $this->db = $pdo;
    }

    /**
     * Обрабатывает логику голосования за пост.
     *
     * Этот метод выполняет все необходимые шаги для регистрации голоса:
     * - Проверяет наличие пользователя или создает его.
     * - Проверяет, голосовал ли пользователь ранее.
     * - Находит ID поста по его URL.
     * - Добавляет новый голос.
     * - Возвращает обновленные счетчики.
     *
     * Все операции выполняются в рамках одной транзакции для обеспечения атомарности.
     *
     * @param string $postUrl URL-адрес поста.
     * @param string $voteType Тип голоса ('like' или 'dislike').
     * @param string $uuid Уникальный идентификатор посетителя.
     * @return array Массив с обновленными счетчиками лайков и дизлайков.
     * @throws ReactionException Если посетитель уже голосовал или пост не найден.
     * @throws Throwable В случае других системных ошибок при работе с БД.
     */
    public function handleVote(string $postUrl, string $voteType, string $uuid): array {
        try {
            $this->db->beginTransaction();

            $visitorId = $this->votingModel->getOrCreateVisitorId($uuid);
            $postId = $this->votingModel->findPostByUrl($postUrl);
            if ($postId === null) {
                throw new ReactionException("Пост с урлом {$postUrl} не найден", 404);
            }
            $hasVoted = $this->votingModel->checkIfVisitorHasAlreadyVoted($visitorId, $postUrl);
            if ($hasVoted) {
                throw new ReactionException();
            }
            $this->votingModel->addNewVote($postId, $visitorId, $voteType);
            $counts = $this->votingModel->getPostLikeDislikeCounters($postId);
            
            $this->db->commit();

            return $counts;

        } catch(Throwable $ex) {
            $this->db->rollBack();
            throw $ex;
        }
    }
}