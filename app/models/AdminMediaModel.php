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
    public function getMediaList(int $limit, int $offset): array
    {
        $sql = "SELECT file_path AS url, alt_text AS alt
                FROM media 
                WHERE status='published'
                ORDER BY updated_at DESC
                LIMIT :limit OFFSET :offset";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Error fetching media list: ", ['limit' => $limit, 'offset' => $offset], $e);
            throw $e;
        }
    }

    /**
     * Запрос к базе данных для получения количества всех изображений
     */
    public function countTotalMedia(): int 
    {
        $sql = "SELECT COUNT(*) FROM media WHERE status='published'";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            Logger::error("Error counting media: ", [], $e);
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

    /**
     * Обновляет изображение по его URL
     * @param string $fileUrl Путь к файлу (file_path)
     * @param string $altText Новое описание
     * @return bool
     */
    public function update(string $filePath, string $altText): bool
    {
        $sql = "UPDATE media 
                SET alt_text = :alt_text, updated_at = NOW() 
                WHERE file_path = :file_path";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':alt_text' => $altText,
                ':file_path' => $filePath
            ]);
        } catch (PDOException $e) {
            Logger::error("Error updating img: ", ['file_path' => $filePath, 'alt_text' => $altText], $e);
            throw $e;
        }
    }

    /**
     * Удаляет запись (проверяет rowCount для точности)
     */
    public function delete(string $fileUrl): bool
    {
        $sql = "DELETE FROM media WHERE file_path = :file_path";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':file_path' => $fileUrl]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Logger::error("Error deleting media record: ", ['url' => $fileUrl], $e);
            throw $e;
        }
    }
}
