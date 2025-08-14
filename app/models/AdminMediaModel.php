<?php
// app/models/AdminMediaModel.php

class AdminMediaModel {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
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
            return 0;
        }
    }

    public function saveImgToMedia($userId, $fileUrl, $fileSize, $imageType, $altText)
    {
        $stmt = $this->db->prepare("
                INSERT INTO media (
                    post_id, user_id, file_name, file_path, file_type, 
                    mime_type, file_size, alt_text, uploaded_at, updated_at
                )
                VALUES (
                    NULL, :user_id, :file_name, :file_path, 'image', 
                    :mime_type, :file_size, :alt_text, NOW(), NOW()
                )
            ");
            $stmt->execute([
                //':post_id' => $newPostId,
                ':user_id' => $userId,
                ':file_name' => basename($fileUrl),
                ':file_path' => $fileUrl,
                ':mime_type' => $imageType,
                ':file_size' => $fileSize,
                'alt_text' => $altText 
            ]);
        

        //$this->db->commit(); // Сохраняем всё
    }
    
}
