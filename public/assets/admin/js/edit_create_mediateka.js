document.addEventListener('DOMContentLoaded', function() {
    const adminRoute = document.getElementById('adminRoute').value;
    const csrfToken = document.getElementById('csrfToken').value;

    //2. Медиатека
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
});
