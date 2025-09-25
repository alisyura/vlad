<?php
// app/models/SubmissionModel.php

/**
 * Class SubmissionModel
 *
 * Модель для работы с данными, связанными с обработкой пользовательских материалов сайта.
 */
class SubmissionModel {
    const FILETYPE_IMAGE = 'image';
    const FILETYPE_VIDEO = 'video';

    private PDO $db;

    /**
     * Конструктор класса SubmissionModel.
     *
     * @param PDO $pdo Объект подключения к базе данных. Внедряется из Dependency Injection
     */
    public function __construct(PDO $pdo)
    {
        $this->db =$pdo;
    }

    /**
     * Возвращает ID первого найденного пользователя с указанной ролью.
     *
     * @param string $adminRoleName Имя роли для поиска (например, 'admin').
     * @return int|null ID пользователя или null, если пользователь с такой ролью не найден.
     */
    public function getAdminId(string $adminRoleName): ?int
    {
        $stmt = $this->db->prepare("
            SELECT u.id 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE r.name = :admin_role_name
                AND u.built_in = 1
            ORDER BY u.id ASC
            LIMIT 1");
        $stmt->execute([':admin_role_name' => $adminRoleName]);
        
        $id = $stmt->fetchColumn();

        return $id !== false ? (int)$id : null;
    }

    /**
     * Сохраняет ссылку на видео в базу данных.
     *
     * @param int $userId ID пользователя, который отправил ссылку.
     * @param string $url Полная ссылка на видео.
     * @param string $source Домен видеохостинга (например, 'youtube.com').
     * @return int ID только что созданной записи в таблице `video_links`.
     */
    public function saveToVideo(int $userId, string $url, string $source): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO video_links (
                user_id, url, source, uploaded_at, updated_at
            )
            VALUES (
                :user_id, :url, :source, NOW(), NOW()
            )
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':url' => $url,
            ':source' => $source
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Сохраняет информацию о загруженном медиафайле в базу данных.
     *
     * @param int $userId ID пользователя, который загрузил файл.
     * @param string $fileUrl Путь к файлу на сервере.
     * @param int $fileSize Размер файла в байтах.
     * @param string $mimeType MIME-тип файла.
     * @param string $altText Альтернативный текст для изображения.
     * @return int ID только что созданной записи в таблице `media`.
     */
    public function saveToMedia(int $userId, string $fileUrl, int $fileSize, 
        string $mimeType, string $altText): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO media (
                user_id, file_name, file_path, file_type, 
                mime_type, file_size, alt_text, uploaded_at, updated_at
            )
            VALUES (
                :user_id, :file_name, :file_path, 'image', 
                :mime_type, :file_size, :alt_text, NOW(), NOW()
            )
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':file_name' => basename($fileUrl),
            ':file_path' => $fileUrl,
            ':mime_type' => $mimeType,
            ':file_size' => $fileSize,
            ':alt_text' =>  $altText 
        ]);

        return (int) $this->db->lastInsertId();
    }
    
    /**
     * Сохраняет новый пост в базу данных.
     *
     * Этот метод вставляет новую запись в таблицу 'posts' со статусом 'pending'.
     * Поля thumbnail_media_id и video_link_media_id могут быть NULL.
     *
     * @param string $title Заголовок поста.
     * @param string $url URL-слаг поста.
     * @param string $content Содержимое поста.
     * @param int $adminId Идентификатор пользователя-администратора, создавшего пост.
     * @param int|null $imgId Идентификатор изображения-миниатюры из таблицы 'media'. Может быть NULL, если изображение не выбрано.
     * @param int|null $videoId Идентификатор видео из таблицы 'media'. Может быть NULL, если видео не прикреплено.
     *
     * @return int Идентификатор (ID) только что созданного поста.
     */
    public function savePost(string $title, string $url, string $content, 
        int $adminId, ?int $imgId, ?int $videoId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO posts (
                url, title, content, user_id, status, article_type, 
                thumbnail_media_id, video_link_id, created_at, updated_at,
                excerpt
            ) VALUES (:url, :title, :content, :user_id, 'pending', 'post', 
                :thumbnail_media_id, :video_link_id, NOW(), NOW(),
                '')
        ");
        $stmt->execute([
            ':url' => $url,
            ':title' => $title, 
            ':content' => $content, 
            ':user_id' => $adminId,
            ':thumbnail_media_id' => $imgId, // Если $imgId равен null, то в БД будет NULL
            ':video_link_id' => $videoId // Если $videoId равен null, то в БД будет NULL
        ]);

        return (int) $this->db->lastInsertId();
    }
}