<?php
// Этот файл может использоваться как для создания, так и для редактирования.
// $data['post'] будет содержать данные поста, если это редактирование,
// иначе он будет пустым (null), и поля будут пустыми.

// Проверяем, есть ли данные поста (для редактирования)
$post = $data['post'] ?? null;

// Заголовок страницы
$pageTitle = $post ? 'Редактировать пост: ' . htmlspecialchars($post['title']) : 'Создать новый пост';

// URL для отправки формы (можно определить в контроллере и передать сюда)
$formAction = $post ? '/' . htmlspecialchars($data['adminRoute']) . '/posts/edit/' . htmlspecialchars($post['id']) : '/' . htmlspecialchars($data['adminRoute']) . '/posts/create';

// Убедимся, что $data['adminRoute'] доступен
$adminRoute = $data['adminRoute'] ?? 'admin';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?= $pageTitle ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($post): // Если это режим редактирования, показываем кнопку "Посмотреть на сайте" ?>
            <?php if (!empty($post['url'])): ?>
                <a href="/<?= htmlspecialchars($post['url']) ?>.html" target="_blank" class="btn btn-sm btn-outline-info me-2">
                    Посмотреть на сайте
                </a>
            <?php endif; ?>
            <a href="/<?= htmlspecialchars($adminRoute) ?>/posts" class="btn btn-sm btn-outline-secondary">
                К списку постов
            </a>
        <?php else: ?>
            <a href="/<?= htmlspecialchars($adminRoute) ?>/posts" class="btn btn-sm btn-outline-secondary">
                К списку постов
            </a>
        <?php endif; ?>
    </div>
</div>

<form action="<?= $formAction ?>" method="POST">
    <div class="row">
        <div class="col-md-9">
            <div class="mb-3">
                <label for="postTitle" class="form-label">Заголовок</label>
                <input type="text" class="form-control" id="postTitle" name="title" 
                       value="<?= htmlspecialchars($post['title'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label for="postUrl" class="form-label">URL (слаг)</label>
                <input type="text" class="form-control" id="postUrl" name="url" 
                       value="<?= htmlspecialchars($post['url'] ?? '') ?>">
                <div class="form-text">Оставьте пустым для автоматической генерации из заголовка.</div>
            </div>

            <div class="mb-3">
                <label for="postContent" class="form-label">Содержание статьи</label>
                <textarea id="postContent" name="content" class="form-control" rows="15">
                    <?= htmlspecialchars($post['content'] ?? '') ?>
                </textarea>
            </div>
            
            <div class="mb-3">
                <label for="postExcerpt" class="form-label">Цитата/Анонс</label>
                <textarea id="postExcerpt" name="excerpt" class="form-control" rows="5">
                    <?= htmlspecialchars($post['excerpt'] ?? '') ?>
                </textarea>
                <div class="form-text">Краткое описание статьи для списков или превью.</div>
            </div>

            <div class="card mb-3">
                <div class="card-header">SEO Опции</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="metaTitle" class="form-label">Мета-заголовок (title)</label>
                        <input type="text" class="form-control" id="metaTitle" name="meta_title"
                               value="<?= htmlspecialchars($post['meta_title'] ?? '') ?>">
                        <div class="form-text">Заголовок для поисковых систем (по умолчанию - заголовок поста).</div>
                    </div>
                    <div class="mb-3">
                        <label for="metaDescription" class="form-label">Мета-описание (description)</label>
                        <textarea class="form-control" id="metaDescription" name="meta_description" rows="3">
                            <?= htmlspecialchars($post['meta_description'] ?? '') ?>
                        </textarea>
                        <div class="form-text">Описание для поисковых систем.</div>
                    </div>
                    <div class="mb-3">
                        <label for="metaKeywords" class="form-label">Мета-ключевые слова (keywords)</label>
                        <input type="text" class="form-control" id="metaKeywords" name="meta_keywords"
                               value="<?= htmlspecialchars($post['meta_keywords'] ?? '') ?>">
                        <div class="form-text">Ключевые слова для поисковых систем, через запятую.</div>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-md-3">
            <div class="card mb-3">
                <div class="card-header">Публикация</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="postStatus" class="form-label">Статус</label>
                        <select class="form-select" id="postStatus" name="status">
                            <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Черновик</option>
                            <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Опубликовано</option>
                            <option value="pending" <?= ($post['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Ожидание</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <?= $post ? 'Обновить пост' : 'Опубликовать пост' ?>
                    </button>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Рубрики</div>
                <div class="card-body">
                    <div class="form-text">Пока нет функционала.</div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Метки</div>
                <div class="card-body">
                    <div class="form-text">Пока нет функционала.</div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Миниатюра поста</div>
                <div class="card-body">
                    <div class="form-text">Пока нет функционала.</div>
                </div>
            </div>

        </div>
    </div>
</form>

