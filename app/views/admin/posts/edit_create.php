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
            <div class="card mb-3" style="z-index: 1;">
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
                    <div id="tagsList" class="d-flex flex-wrap gap-2">
                        </div>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-header">Миниатюра поста</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="thumbnail" class="form-label">Загрузить файл</label>
                        <input class="form-control" type="file" id="thumbnail" name="thumbnail">
                    </div>
                    <?php if (!empty($post['thumbnail_url'])): // Заглушка, если есть URL миниатюры ?>
                        <div class="mt-3">
                            <img src="<?= htmlspecialchars($post['thumbnail_url']) ?>" class="img-fluid" alt="Миниатюра">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function() {
    tinymce.init({
        selector: '#postContent', // ID вашего textarea
        plugins: 'advcode link image lists table code media fullscreen',
        toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | code | fullscreen',
        menubar: 'file edit view insert format tools table help',
        height: 600,
        language: 'ru',
        extended_valid_elements: 'p[class|id|style]',
        valid_elements: '*[*]',
        file_picker_callback: function (cb, value, meta) {
            alert('Функционал файлового менеджера пока не реализован.');
        }
    });

    const tagsInput = document.getElementById('tagsInput');
    const tagsList = document.getElementById('tagsList');
    const existingTags = <?php echo json_encode($data['tags'] ?? []); ?>;

    // Функция для создания тега в интерфейсе
    function createTagElement(tagUrl, tagName) {
        const tagSpan = document.createElement('span');
        tagSpan.className = 'badge bg-secondary d-flex align-items-center me-2 mb-2';
        tagSpan.innerHTML = `
            ${tagName}
            <input type="hidden" name="tags[]" value="${tagUrl}">
            <button type="button" class="btn-close btn-close-white ms-2" aria-label="Remove tag"></button>
        `;

        const closeButton = tagSpan.querySelector('.btn-close');
        closeButton.addEventListener('click', () => {
            tagSpan.remove();
        });

        return tagSpan;
    }

    // Инициализация существующих тегов (при редактировании или ошибке)
    const selectedTags = <?php echo json_encode($post['selected_tags'] ?? []); ?>;
    if (selectedTags.length > 0) {
        selectedTags.forEach(tagUrl => {
            // Нужно найти имя тега по URL
            const existingTag = existingTags.find(t => t.url === tagUrl);
            const tagName = existingTag ? existingTag.name : tagUrl.replace(/-/g, ' ');
            tagsList.appendChild(createTagElement(tagUrl, tagName));
        });
    }

    // Обработчик нажатия на Enter или запятую в поле ввода
    tagsInput.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' || event.key === ',') {
            event.preventDefault();
            const tagValue = this.value.trim();
            if (tagValue) {
                // Преобразуем имя в URL-формат
                const tagUrl = tagValue.toLowerCase().replace(/[^a-z0-9- ]/g, '').replace(/ /g, '-');
                // Проверяем, что тег не добавлен
                const existingInput = tagsList.querySelector(`input[value="${tagUrl}"]`);
                if (!existingInput) {
                    tagsList.appendChild(createTagElement(tagUrl, tagValue));
                }
            }
            this.value = '';
        }
    });

    // Обработчик нажатия на пробел для автозамены на дефис
    tagsInput.addEventListener('input', function() {
        this.value = this.value.replace(/ /g, '-');
    });
});
</script>