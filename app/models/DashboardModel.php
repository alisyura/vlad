<?php

class DashboardModel {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getRecentActivities()
    {
        try {
            $sql = "
                (
                    SELECT 
                        'Создан пост' AS action,
                        p.title AS target,
                        u.name AS user,
                        p.created_at AS date
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.article_type = 'post'
                )
                UNION ALL
                (
                    SELECT 
                        'Обновлён пост' AS action,
                        p.title AS target,
                        u.name AS user,
                        p.updated_at AS date
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.article_type = 'post' 
                    AND p.updated_at IS NOT NULL 
                    AND p.updated_at != p.created_at
                )
                UNION ALL
                (
                    SELECT 
                        'Создана страница' AS action,
                        p.title AS target,
                        u.name AS user,
                        p.created_at AS date
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.article_type = 'page'
                )
                UNION ALL
                (
                    SELECT 
                        'Обновлена страница' AS action,
                        p.title AS target,
                        u.name AS user,
                        p.updated_at AS date
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.article_type = 'page'
                    AND p.updated_at IS NOT NULL 
                    AND p.updated_at != p.created_at
                )
                ORDER BY date DESC
                LIMIT 10
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $raw_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $activities = [];
            foreach ($raw_activities as $activity) {
                $activities[] = [
                    'action' => $activity['action'] . ': "' . htmlspecialchars($activity['target']) . '"',
                    'user' => htmlspecialchars($activity['user']),
                    'date' => date('d.m.Y H:i', strtotime($activity['date']))
                ];
            }

            return $activities;

        } catch (PDOException $e) {
            Logger::error("Database error in DashboardModel::getRecentActivities: " . $e->getTraceAsString());
            return [];
        }
    }

    public function getPostsCount()
    {
        try {
            // Считаем только посты (article_type = 'post')
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM posts WHERE article_type = 'post'");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            Logger::error("Database error in DashboardModel::getPostCount: " . $e->getTraceAsString());
            return 0;
        }
    }

    public function getPagesCount()
    {
        try {
            // Считаем только посты (article_type = 'post')
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM posts WHERE article_type = 'page'");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            Logger::error("Database error in DashboardModel::getPagesCount: " . $e->getTraceAsString());
            return 0;
        }
    }

    public function getUsersCount()
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            Logger::error("Database error in DashboardModel::getUsersCount: " . $e->getTraceAsString());
            return 0;
        }
    }
}