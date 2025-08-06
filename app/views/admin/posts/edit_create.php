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

<div class="modal fade" id="mediaModal" tabindex="-1" aria-labelledby="mediaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaModalLabel">Медиатека</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="mediaUpload" class="form-label">Загрузить новое изображение</label>
                    <input class="form-control" type="file" id="mediaUpload" accept="image/*">
                </div>
                <hr>
                <h5>Выбрать из существующих</h5>
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3" id="mediaGallery">
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-primary" id="insertMediaBtn" disabled>Вставить</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const adminRoute = '<?= htmlspecialchars($adminRoute) ?>';
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;

        tinymce.init({
            selector: '#postContent',
            plugins: 'link image lists code media emoticons wordcount',
            toolbar: 'undo redo | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link image | emoticons | code',
            menubar: false,
            height: 600,
            language: 'ru',
            extended_valid_elements: 'p[class|id|style]',
            valid_elements: '*[*]',
            license_key: 'gpl',
            file_picker_callback: function (cb, value, meta) {
                if (meta.filetype === 'image') {
                    currentCallback = cb; // Сохраняем колбэк для передачи URL
                    loadMediaItems(); // Загружаем картинки в модалку
                    mediaModal.show(); // Показываем модальное окно
                }
            },
            branding: false
        });

        const tagsInput = document.getElementById('tagsInput');
        const tagsList = document.getElementById('tagsList');
        const tagSuggestions = document.getElementById('tagSuggestions');
        
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
        
        function addTag(tagUrl, tagName) {
            const existingInput = tagsList.querySelector(`input[value="${tagUrl}"]`);
            if (!existingInput) {
                tagsList.appendChild(createTagElement(tagUrl, tagName));
            }
        }
        
        const selectedTags = <?php echo json_encode($post['selected_tags'] ?? []); ?>;
        const existingTags = <?php echo json_encode($data['tags'] ?? []); ?>;
        if (selectedTags.length > 0) {
            selectedTags.forEach(tagUrl => {
                const existingTag = existingTags.find(t => t.url === tagUrl);
                const tagName = existingTag ? existingTag.name : tagUrl.replace(/-/g, ' ');
                addTag(tagUrl, tagName);
            });
        }
        
        tagsInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const tagValue = this.value.trim();
                
                if (tagValue) {
                    // Разделяем строку на отдельные теги по запятой
                    const tags = tagValue.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
                    
                    tags.forEach(tagName => {
                        const tagUrl = tagName.toLowerCase()
                                            .replace(/[^a-zа-яё0-9- ]/g, '')
                                            .replace(/ /g, '-');
                        addTag(tagUrl, tagName);
                    });
                }
                
                this.value = '';
                tagSuggestions.innerHTML = '';
            }
        });
        
        let debounceTimeout;
        tagsInput.addEventListener('input', function() {
            clearTimeout(debounceTimeout);
            const query = this.value.trim();

            if (query.length < 3) {
                tagSuggestions.innerHTML = '';
                return;
            }

            debounceTimeout = setTimeout(async () => {
                try {
                    const url = `/${adminRoute}/tags/search`;
                    
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-Token': csrfToken
                        },
                        body: JSON.stringify({ q: query })
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const tags = await response.json();
                    
                    tagSuggestions.innerHTML = '';
                    if (tags.length > 0) {
                        tags.forEach(tag => {
                            const suggestionItem = document.createElement('a');
                            suggestionItem.href = '#';
                            suggestionItem.className = 'list-group-item list-group-item-action';
                            suggestionItem.textContent = tag.name;
                            suggestionItem.addEventListener('click', (e) => {
                                e.preventDefault();
                                addTag(tag.url, tag.name);
                                tagsInput.value = '';
                                tagSuggestions.innerHTML = '';
                            });
                            tagSuggestions.appendChild(suggestionItem);
                        });
                    }
                } catch (error) {
                    console.error('Ошибка при поиске меток:', error);
                }
            }, 300);
        });
        
        document.addEventListener('click', (e) => {
            if (!tagsInput.contains(e.target) && !tagSuggestions.contains(e.target)) {
                tagSuggestions.innerHTML = '';
            }
        });





        // Определяем переменные для работы с медиатекой
        const mediaModal = new bootstrap.Modal(document.getElementById('mediaModal'));
        const mediaGallery = document.getElementById('mediaGallery');
        const mediaUploadInput = document.getElementById('mediaUpload');
        const insertMediaBtn = document.getElementById('insertMediaBtn');

        let currentCallback;

        // Функция для загрузки и отображения картинок из медиатеки
        async function loadMediaItems() {
            // В следующих шагах мы создадим этот роут на сервере
            const url = `/${adminRoute}/media/list`;

            try {
                console.log('loadMediaItems fetch');
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                console.log('loadMediaItems !response.ok');
                // Проверяем, что ответ успешен
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                console.log('loadMediaItems await response.text');
                // Получаем текст ответа, чтобы избежать ошибки парсинга JSON
                const responseText = await response.text();

                console.log('loadMediaItems JSON.parse(responseText)');
                // Теперь попробуем распарсить JSON. Если здесь опять ошибка,
                // значит, ответ действительно не JSON.
                const items = JSON.parse(responseText);

                mediaGallery.innerHTML = '';
                items.forEach(item => {
                    const itemElement = document.createElement('div');
                    itemElement.className = 'col media-item';
                    itemElement.innerHTML = `
                        <img src="${item.url}" class="img-thumbnail" alt="${item.alt}" data-url="${item.url}">
                    `;
                    mediaGallery.appendChild(itemElement);

                    itemElement.addEventListener('click', () => {
                        // Снимаем выделение со всех картинок
                        document.querySelectorAll('.media-item img').forEach(img => img.classList.remove('selected'));
                        // Выделяем текущую
                        itemElement.querySelector('img').classList.add('selected');
                        insertMediaBtn.disabled = false; // Активируем кнопку "Вставить"
                    });
                });

            } catch (error) {
                console.error('Ошибка при загрузке медиатеки:', error);
            }
        }

        // Обработчик нажатия на кнопку "Вставить"
        insertMediaBtn.addEventListener('click', () => {
            const selectedImage = document.querySelector('.media-item img.selected');
            if (selectedImage && currentCallback) {
                currentCallback(selectedImage.dataset.url); // Передаём URL картинки в TinyMCE
                mediaModal.hide(); // Закрываем модальное окно
            }
        });

        // Обработчик загрузки файла
        mediaUploadInput.addEventListener('change', async (event) => {
            const file = event.target.files[0];
            if (file) {
                // В следующих шагах мы создадим этот роут на сервере
                const url = `/${adminRoute}/media/upload`;
                
                const formData = new FormData();
                formData.append('file', file);
                formData.append('csrf_token', csrfToken);

                try {
                    console.log('upload fetch');
                    const response = await fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    console.log('upload response.ok');
                    if (response.ok) {
                        // Если загрузка успешна, обновляем галерею
                        console.log('upload retData');
                        const retData = await response.json();
                        console.log('retData '+JSON.stringify(retData));
                        console.log('upload await loadMediaItems');
                        await loadMediaItems();
                        event.target.value = ''; // Очищаем поле ввода файла
                    } else {
                        console.log('upload await response.json');
                        const errorData = await response.json();
                        alert('Ошибка загрузки: ' + errorData.error);
                    }
                } catch (error) {
                    console.error('Ошибка при загрузке файла:', error);
                }
            }
        });
    });
</script>