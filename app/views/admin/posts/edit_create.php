<?php
// Этот файл может использоваться как для создания, так и для редактирования.
// $data['post'] будет содержать данные поста, если это редактирование,
// иначе он будет пустым (null), и поля будут пустыми.

// Проверяем, есть ли данные поста (для редактирования)
$post = $data['post'] ?? null;
$categories = $data['categories'] ?? [];
$tags = $data['tags'] ?? [];
$selectedCategories = $post['selected_categories'] ?? []; // Для формы создания, если были ошибки
$selectedTags = $post['selected_tags'] ?? [];

// Заголовок страницы
$pageTitle = !$is_new_post ? 'Редактировать пост: ' . htmlspecialchars($post['title']) : 'Создать новый пост';

// URL для отправки формы (можно определить в контроллере и передать сюда)
$formAction = !$is_new_post ? '/' . htmlspecialchars($adminRoute) . '/posts/edit/' . htmlspecialchars($post['id']) : '/' . htmlspecialchars($adminRoute) . '/posts/create';

// Убедимся, что $data['adminRoute'] доступен
$adminRoute = $data['adminRoute'] ?? 'admin';
?>
<input type="hidden" id="initialTagsData" value='<?= htmlspecialchars(json_encode($data['tags'] ?? []), ENT_QUOTES, 'UTF-8') ?>'>
<input type="hidden" id="selectedTagsData" value='<?= htmlspecialchars(json_encode($post['selected_tags'] ?? []), ENT_QUOTES, 'UTF-8') ?>'>
<input type="hidden" id="csrfToken" name="csrf_token" value="<?= htmlspecialchars($data['csrf_token']) ?>">
<input type="hidden" id="adminRoute" value="<?= htmlspecialchars($adminRoute) ?>">
<input type="hidden" id="articleType" value="<?= htmlspecialchars($articleType) ?>">

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

<?php if (!empty($data['errors'])): ?>
    <div class="alert alert-danger" role="alert">
        <?php foreach ($data['errors'] as $error): ?>
            <?= htmlspecialchars($error) ?><br>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form action="<?= $formAction ?>" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data['csrf_token']) ?>">
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
                        <label class="form-label">Статус</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="statusDraft" value="draft"
                                <?= ($post['status'] ?? 'draft') === 'draft' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="statusDraft">
                                Черновик
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="statusPublished" value="published"
                                <?= ($post['status'] ?? '') === 'published' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="statusPublished">
                                Опубликовано
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="statusPending" value="pending"
                                <?= ($post['status'] ?? '') === 'pending' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="statusPending">
                                Ожидание
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <?= !$is_new_post ? 'Обновить пост' : 'Опубликовать пост' ?>
                    </button>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Рубрики</div>
                <div class="card-body">
                    <?php if (!empty($categories)): ?>
                        <div class="list-group">
                            <?php foreach ($categories as $category): ?>
                                <label class="list-group-item">
                                    <input class="form-check-input me-1" type="checkbox" name="categories[]" value="<?= htmlspecialchars($category['id']) ?>" 
                                           <?= in_array($category['id'], $selectedCategories) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="form-text text-muted">Категорий нет. Создайте их в разделе "Рубрики".</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Метки</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="tagsInput" class="form-label">Добавить метки</label>
                        <input type="text" class="form-control" id="tagsInput" name="tags" placeholder="Метки через запятую">
                        <div class="form-text">Вводите метки через запятую. Если метка не существует, она будет создана.</div>
                    </div>
                    <div id="tagSuggestions" class="list-group mb-3" style="position: relative;">
                    </div>
                    <div id="tagsList" class="d-flex flex-wrap gap-2">
                        </div>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-header">Миниатюра поста</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="postImageInput" class="form-label">Выбрать изображение</label>
                        <div class="d-flex flex-column">
                            <button type="button" class="btn btn-secondary mb-2" id="openImageModalBtn">
                                Выбрать изображение
                            </button>
                            
                            <button type="button" class="btn btn-outline-danger btn-sm" id="removeImageBtn" style="<?= empty($post['thumbnail_url']) ? 'display: none;' : '' ?>">
                                Удалить миниатюру
                            </button>
                        </div>
                        
                        <input type="hidden" id="postImageInput" name="post_image_url" value="<?= htmlspecialchars($post['thumbnail_url'] ?? '') ?>">

                        <div id="selectedImagePreview" class="mt-3" style="max-width: 200px; <?= !empty($post['thumbnail_url']) ? 'display: block;' : 'display: none;' ?>">
                            <img id="postImagePreview" src="<?= htmlspecialchars($post['thumbnail_url'] ?? '') ?>" class="img-thumbnail w-100" alt="Миниатюра поста">
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</form>

<div class="modal fade" id="mediaModal" tabindex="-1" aria-labelledby="mediaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaModalLabel">Медиатека</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-3">
            <h5>Загрузить новое изображение</h5>
            <div class="mb-3 p-3 border rounded">
                <form id="mediaUploadForm" enctype="multipart/form-data">
                    <div class="row align-items-end">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <!-- <label for="mediaUpload" class="form-label">Файл</label> -->
                            <input class="form-control" type="file" id="mediaUpload" name="file" accept="image/*" required>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <!-- <label for="altText" class="form-label">Alt-текст</label> -->
                            <input class="form-control" type="text" id="altText" name="alt" placeholder="Описание для SEO и доступности" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Добавить</button>
                        </div>
                    </div>
                </form>
            </div>
                <hr>
                <h5>Выбрать из существующих</h5>
                
                <div id="mediaGalleryContainer" style="max-height: 45vh; overflow-y: auto;">
                    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3" id="mediaGallery">
                        </div>
                </div>
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-primary" id="insertMediaBtn" disabled>Вставить</button>
            </div>
        </div>
    </div>
</div>

