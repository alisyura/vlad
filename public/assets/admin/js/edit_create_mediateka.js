document.addEventListener('DOMContentLoaded', function() {
    const adminRoute = document.getElementById('adminRoute').value;
    const csrfToken = document.getElementById('csrfToken').value;

    //2. Медиатека
    // Определяем переменные для работы с медиатекой
    const mediaModal = new bootstrap.Modal(document.getElementById('mediaModal'));
    const mediaGallery = document.getElementById('mediaGallery');
    const insertMediaBtn = document.getElementById('insertMediaBtn');

    const mediaUploadForm = document.getElementById('mediaUploadForm');
    const mediaUploadInput = document.getElementById('mediaUpload'); // Переменная уже была, но теперь она часть формы
    const altTextInput = document.getElementById('altText');

    // Новые переменные для миниатюры
    const openImageModalBtn = document.getElementById('openImageModalBtn');
    const postImageInput = document.getElementById('postImageInput');
    const postImagePreview = document.getElementById('postImagePreview');
    const selectedImagePreview = document.getElementById('selectedImagePreview');
    const removeImageBtn = document.getElementById('removeImageBtn');

    let currentCallback;

    // Обработчик для кнопки "Выбрать изображение"
    if (openImageModalBtn) {
        openImageModalBtn.addEventListener('click', () => {
            currentCallback = function(imageUrl) {
                postImageInput.value = imageUrl;
                postImagePreview.src = imageUrl;
                selectedImagePreview.style.display = 'block'; // Показываем превью
                removeImageBtn.style.display = 'block'; // Показываем кнопку "Удалить"
                mediaModal.hide();
            };
            
            loadMediaItems();
            mediaModal.show();
        });
    }

    // Обработчик для кнопки "Удалить миниатюру"
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', () => {
            postImageInput.value = '';
            postImagePreview.src = '';
            selectedImagePreview.style.display = 'none'; // Скрываем превью
            removeImageBtn.style.display = 'none'; // Скрываем кнопку "Удалить"
        });
    }

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

                // НОВЫЙ обработчик ДВОЙНОГО клика
                itemElement.addEventListener('dblclick', () => {
                    const imageUrl = itemElement.querySelector('img').dataset.url;
                    const altText = itemElement.querySelector('img').alt;
                    if (currentCallback) {
                        const relativeUrl = imageUrl.replace('../../', '/');
                        currentCallback(relativeUrl, altText);
                        mediaModal.hide();
                    }
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
            const imageUrl = selectedImage.dataset.url;
            const altText = selectedImage.alt; // Получаем alt-текст
            const relativeUrl = imageUrl.replace('../../', '/');
            currentCallback(relativeUrl, altText); // Передаём URL картинки в TinyMCE
            mediaModal.hide(); // Закрываем модальное окно
        }
    });

    // НОВЫЙ обработчик загрузки файла
    mediaUploadForm.addEventListener('submit', async (event) => {
        event.preventDefault(); // Отменяем стандартную отправку формы
        
        const file = mediaUploadInput.files[0];
        const altText = altTextInput.value;

        if (file && altText) {
            const url = `/${adminRoute}/media/upload`;
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('alt', altText); // Добавляем alt-текст в FormData
            formData.append('csrf_token', csrfToken);

            try {
                // ... ваш код для fetch запроса ...
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    await response.json();
                    await loadMediaItems();
                    // Очищаем форму после успешной загрузки
                    mediaUploadForm.reset();
                } else {
                    const errorData = await response.json();
                    alert('Ошибка загрузки: ' + errorData.error);
                }
            } catch (error) {
                console.error('Ошибка при загрузке файла:', error);
            }
        } else {
            alert('Пожалуйста, выберите файл и укажите Alt-текст.');
        }
    });

    // --- НОВАЯ ФУНКЦИЯ ДЛЯ ОТКРЫТИЯ ДИАЛОГА ---
    function openMyCustomImageDialog(editor) {
        // Очищаем предыдущее выделение и отключаем кнопку
        document.querySelectorAll('.media-item img').forEach(img => img.classList.remove('selected'));
        insertMediaBtn.disabled = true;

        // Определяем колбэк, который будет вставлять изображение в редактор
        currentCallback = function(imageUrl, altText) {
            // Вставляем изображение, используя стандартную команду TinyMCE
            const relativeUrl = imageUrl.replace('../../', '/');
            editor.insertContent(`<img src="${relativeUrl}" alt="${altText}">`);
        };

        // Загружаем список медиафайлов и открываем модальное окно
        loadMediaItems();
        mediaModal.show();
    }
    // --- КОНЕЦ НОВОЙ ФУНКЦИИ ---

    tinymce.init({
        selector: '#postContent',
        plugins: 'link  lists code media emoticons wordcount',
        toolbar: 'undo redo | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link mycustomimage | emoticons | code',
        menubar: false,
        height: 600,
        language: 'ru',
        extended_valid_elements: 'p[class|id|style]',
        valid_elements: '*[*]',
        license_key: 'gpl',
        convert_urls: false,
        setup: function(editor) {
            editor.ui.registry.addButton('mycustomimage', {
            icon: 'image',
            tooltip: 'Insert/Edit Image (Custom)',
            onAction: function() {
                openMyCustomImageDialog(editor);
            }
            });

            // Опционально: если где-то вызывается mceImage — тоже перехватываем
            editor.addCommand('mceImage', function() {
                openMyCustomImageDialog(editor);
            });
        },
        branding: false
    });
});
