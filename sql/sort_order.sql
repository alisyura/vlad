-- 1. Добавляем поле sort_order (если его еще нет)
ALTER TABLE posts 
ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER id;

-- 2. Заполняем sort_order начальными значениями (например, по id)
UPDATE your_table_name SET sort_order = id;

-- 3. Удаляем старые ненужные индексы
DROP INDEX IF EXISTS idx_posts_created ON posts;
DROP INDEX IF EXISTS idx_posts_type_created ON posts;

-- 4. Создаем правильный композитный индекс для главной страницы
CREATE INDEX idx_main_query_sort ON posts 
(status, article_type, sort_order, created_at);

