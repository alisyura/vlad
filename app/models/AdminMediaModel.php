<?php
// app/models/AdminMediaModel.php

class AdminMediaModel extends BaseModel {
    public function __construct(PDO $db)
    {
        parent::__construct($db);
    }
    
    /**
     * Запрос к базе данных для получения всех изображений
     */
    public function getMediaList()
    {
        $sql = "SELECT file_path AS url, alt_text AS alt
                FROM media 
                WHERE status='published'
                ORDER BY updated_at DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Error fetching media list: " . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Сохраняет информацию об изображении в таблице 'media'.
     *
     * @param int $userId Идентификатор пользователя, загрузившего файл.
     * @param string $fileUrl Путь к сохраненному файлу изображения. /assets/uploads...
     * @param int $fileSize Размер файла в байтах.
     * @param string $imageType MIME-тип изображения (например, 'image/jpeg', 'image/png').
     * @param string $altText Альтернативный текст для изображения (для SEO/доступности).
     * @return void
     * @throws \PDOException Если происходит ошибка выполнения запроса к базе данных.
     */
    public function saveImgToMedia(int $userId, string $fileUrl, int $fileSize, 
        string $imageType, string $altText): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO media (
                user_id, file_name, file_path,  
                mime_type, file_size, alt_text, uploaded_at, updated_at
            )
            VALUES (
                :user_id, :file_name, :file_path, 
                :mime_type, :file_size, :alt_text, NOW(), NOW()
            )
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':file_name' => basename($fileUrl),
            ':file_path' => $fileUrl,
            ':mime_type' => $imageType,
            ':file_size' => $fileSize,
            ':alt_text' =>  $altText 
        ]);
    }
    
    /**
     * Получает ID медиафайла по его URL (file_path).
     * @param string $fileUrl URL файла.
     * @return int|null ID файла или null, если не найден.
     */
    public function getMediaIdByUrl(string $fileUrl): ?int
    {
        if (empty($fileUrl)) {
            return null;
        }
        $sql = "SELECT id FROM media WHERE file_path = :file_path LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':file_path' => $fileUrl]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int) $result['id'] : null;
    }
}
