<?php
// app/services/SubmissionService.php

class SubmissionService
{
    private MediaUploadService $mediaUploadService;
    private PDO $db;
    private SubmissionModel $model;

    public function __construct(MediaUploadService $mediaUploadService, 
        SubmissionModel $submissionModel, PDO $pdo)
    {
        $this->mediaUploadService = $mediaUploadService;
        $this->db = $pdo;
        $this->model = $submissionModel;
    }

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