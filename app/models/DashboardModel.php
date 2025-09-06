<?php
// app/models/DashboardModel.php
class DashboardModel extends BaseModel {
    public function getRecentActivities()
    {
        try {
            $sql = "
            (
                SELECT
                    'Создан пост' AS action,
                    p.title COLLATE utf8mb4_unicode_ci AS target,
                    u.name AS user,
                    p.created_at AS date
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.article_type = 'post' 
                    AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            )
            UNION ALL
            (
                SELECT
                    'Обновлён пост' AS action,
                    p.title COLLATE utf8mb4_unicode_ci AS target,
                    u.name AS user,
                    p.updated_at AS date
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.article_type = 'post'
                    AND p.updated_at IS NOT NULL
                    AND p.updated_at != p.created_at
                    AND p.updated_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            )
            UNION ALL
            (
                SELECT
                    'Создана страница' AS action,
                    p.title COLLATE utf8mb4_unicode_ci AS target,
                    u.name AS user,
                    p.created_at AS date
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.article_type = 'page' 
                    AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            )
            UNION ALL
            (
                SELECT
                    'Обновлена страница' AS action,
                    p.title COLLATE utf8mb4_unicode_ci AS target,
                    u.name AS user,
                    p.updated_at AS date
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.article_type = 'page'
                    AND p.updated_at IS NOT NULL
                    AND p.updated_at != p.created_at
                    AND p.updated_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            )
            UNION ALL
            (
                SELECT
                    'Создан пользователь' AS action,
                    u.name COLLATE utf8mb4_unicode_ci AS target,
                    u.name AS user,
                    u.created_at AS date
                FROM users u
                WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            )
            UNION ALL
            (
                SELECT
                    'Обновлён пользователь' AS action,
                    u.name COLLATE utf8mb4_unicode_ci AS target,
                    u.name AS user,
                    u.updated_at AS date
                FROM users u
                WHERE u.updated_at IS NOT NULL
                    AND u.updated_at != u.created_at
                    AND u.updated_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            )
            UNION ALL
            (
                SELECT
                    'Создан тэг' AS action,
                    t.name COLLATE utf8mb4_unicode_ci AS target,
                    '' AS user,
                    t.created_at AS date
                FROM tags t
                WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            )
            UNION ALL
            (
                SELECT
                    'Обновлён тэг' AS action,
                    t.name COLLATE utf8mb4_unicode_ci AS target,
                    '' AS user,
                    t.updated_at AS date
                FROM tags t
                WHERE t.updated_at IS NOT NULL
                    AND t.updated_at != t.created_at
                    AND t.updated_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            )
            ORDER BY date DESC
            LIMIT 10;
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