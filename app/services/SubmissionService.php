<?php
// app/services/SubmissionService.php

/**
 * Сервис для обработки пользовательских материалов.
 *
 * Отвечает за прием, валидацию и сохранение предложенных пользователем
 * материалов (текста, видео, изображений) в базе данных. Обеспечивает
 * целостность данных с помощью транзакций и откат в случае ошибок.
 */
class SubmissionService
{
    private MediaUploadService $mediaUploadService;
    private PDO $db;
    private SubmissionModel $model;

    /**
     * Конструктор SubmissionService.
     *
     * @param MediaUploadService $mediaUploadService Сервис для загрузки файлов.
     * @param SubmissionModel $submissionModel Модель для работы с данными о материалах.
     * @param PDO $pdo Объект PDO для работы с базой данных.
     */
    public function __construct(MediaUploadService $mediaUploadService, 
        SubmissionModel $submissionModel, PDO $pdo)
    {
        $this->mediaUploadService = $mediaUploadService;
        $this->db = $pdo;
        $this->model = $submissionModel;
    }

    /**
     * Обрабатывает отправленный пользователем материал.
     *
     * Выполняет поиск администратора, базовую валидацию контента,
     * загрузку и сохранение файлов/ссылок и сохранение поста в базе данных
     * в рамках одной транзакции.
     *
     * @param string $adminRoleName Имя роли администратора, которому назначается пост.
     * @param string $content Текст материала.
     * @param string|null $videoLink Ссылка на видео.
     * @param array|null $file Данные загруженного файла (например, изображения).
     * @throws SubmissionException Если администратор не найден, текст пуст,
     * или произошла ошибка при загрузке изображения.
     * @throws Throwable В случае непредвиденных системных ошибок.
     */
    public function handleSubmission(string $adminRoleName, string $content, 
        ?string $videoLink, ?array $file): void
    {
        $adminId = $this->model->getAdminId($adminRoleName);
        if ($adminId === null) {
            throw new SubmissionException("Админ с ролью '{$adminRoleName}' не найден", SubmissionException::ADMIN_NOT_FOUND);
        }

        if (empty(trim($content))) {
            throw new SubmissionException("Текст материала пустой", SubmissionException::CONTENT_EMPTY);
        }

        try {
            $this->db->beginTransaction();

            // обработка приложенной картинки
            $uploadedFile = null;
            $imgId = null;
            if (!empty($file)) {
                $uploadedFile = $this->mediaUploadService->handleUpload($file);
                $imgId = $this->model->saveToMedia(
                    $adminId, 
                    $uploadedFile['url'],
                    $uploadedFile['size'],
                    $uploadedFile['mime'],
                    '');
            }

            $videoId = null;
            if (!empty(trim($videoLink)))
            {
                $videoId = $this->model->saveToVideo(
                    $adminId, 
                    $videoLink,
                    extractDomainFromUrl($videoLink));
            }

            $currentDate = date('Y-m-d H:i:s');
            $title = "Пост от " . date('d.m.Y');
            $url = transliterate("Предложенный материал {$currentDate}");
            $this->model->savePost($title, $url, $content, $adminId, 
                $imgId, $videoId);

            $this->db->commit();
        } catch (MediaException $e) {
            $this->rollBack($uploadedFile);
            throw new SubmissionException($e->getMessage(), 
                SubmissionException::IMAGE_SIZE_INCORRECT, $e);
        } catch (Throwable $e) {
            $this->rollBack($uploadedFile);
            Logger::error('Error processing user submission.', [$e->getTraceAsString()]);
            throw $e;
        }

    }

    /**
     * Выполняет откат транзакции и удаление загруженного файла в случае ошибки.
     *
     * Эта функция-помощник гарантирует, что база данных останется в
     * консистентном состоянии, а на сервере не останутся лишние файлы.
     *
     * @param array|null $uploadedFile Информация о загруженном файле, если он был.
     * @return void
     */
    private function rollBack(?array $uploadedFile): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        if (is_array($uploadedFile) && file_exists($uploadedFile['targetFile'])) {
            unlink($uploadedFile['targetFile']);
        }
    }
}