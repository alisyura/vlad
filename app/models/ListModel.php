<?php
// app/models/ListModel.php

class ListModel extends BaseModel {
    public function __construct(PDO $db)
    {
        parent::__construct($db);
    }
    
    /**
     * Получает все категории из базы данных.
     * @return array Список категорий.
     */
    public function getAllCategories(): array {
        $sql = "SELECT id, name FROM categories WHERE url <> 'tegi' ORDER BY name ASC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Error fetching all categories: " . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Получает все теги из базы данных.
     * @return array Список тегов.
     */
    public function getAllTags(): array {
        $sql = "SELECT id, name, url FROM tags ORDER BY name ASC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Error fetching all tags: " . $e->getTraceAsString());
            return [];
        }
    }
}