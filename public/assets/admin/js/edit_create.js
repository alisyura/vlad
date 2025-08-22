document.addEventListener('DOMContentLoaded', function() {
    const postTitleInput = document.getElementById('postTitle');
    const postUrlInput = document.getElementById('postUrl');
    const articleTypeInput = document.getElementById('articleType');
    const savePostBtn = document.querySelector('button[type="button"]');
    const postForm = document.querySelector('form');
    const postContentTextarea = document.getElementById('postContent');

    // const adminRoute = document.querySelector('meta[name="admin_route"]')?.content || 'admin';
    
    // ИЗМЕНЕНИЕ ЗДЕСЬ: Теперь CSRF-токен берётся только из meta-тега.
    const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content;

    let slugTimeout = null;
    let slugCheckTimeout = null;
    
    // Новая функция для синхронизации контента редактора
    function syncEditorContentBeforeValidation() {
        if (typeof tinymce !== 'undefined' && tinymce.get('postContent')) {
            tinymce.triggerSave();
        } else if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['postContent']) {
            CKEDITOR.instances['postContent'].updateElement();
        }
    }

    // Функция для отображения ошибок в виде alert-блока
    function displayErrors(errors) {
        clearErrors(); // Сначала очищаем старые ошибки
        
        const errorDiv = document.createElement('div');
        errorDiv.classList.add('alert', 'alert-danger');
        errorDiv.setAttribute('role', 'alert');
        
        const errorHtml = errors.map(error => htmlspecialchars(error)).join('<br>');
        errorDiv.innerHTML = errorHtml;
        
        postForm.parentNode.insertBefore(errorDiv, postForm);
    }
    
    // Функция для очистки ошибок
    function clearErrors() {
        const existingErrorDiv = document.querySelector('.alert.alert-danger');
        if (existingErrorDiv) {
            existingErrorDiv.remove();
        }
    }
    
    // Вспомогательная функция для безопасности
    function htmlspecialchars(str) {
      const div = document.createElement('div');
      div.innerText = str;
      return div.innerHTML;
    }
    
    // Функция для проверки осмысленного контента
    function isContentMeaningful(content) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = content;
        const plainText = tempDiv.textContent || tempDiv.innerText || '';
        return plainText.trim() !== '';
    }

    // Генерация слага из заголовка
    if (postTitleInput && postUrlInput) {
        postTitleInput.addEventListener('input', function() {
            if (postUrlInput.value === '') {
                if (slugTimeout) {
                    clearTimeout(slugTimeout);
                }
                slugTimeout = setTimeout(() => {
                    const title = this.value;
                    const slug = generateSlug(title);
                    postUrlInput.value = slug;
                    checkSlugAvailability(slug);
                }, 2500);
            }
        });
    }

    // Транслитерация слага и проверка уникальности при ручном вводе
    if (postUrlInput) {
        postUrlInput.addEventListener('input', function() {
            let value = this.value;
            value = transliterate(value);
            value = value.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/--+/g, '-').replace(/^-|-$/g, '');
            this.value = value;
            
            if (slugCheckTimeout) {
                clearTimeout(slugCheckTimeout);
            }
            slugCheckTimeout = setTimeout(() => {
                checkSlugAvailability(value);
            }, 500);
        });
    }

    // Обработка клика по кнопке "Опубликовать"
    if (savePostBtn && postForm) {
        savePostBtn.addEventListener('click', async function(e) {
            e.preventDefault();

            // *** НАЧАЛО: Клиентская валидация обязательных полей ***
            let hasErrors = false;
            let errors = [];

            clearErrors(); // Очищаем ошибки перед новой проверкой
            
            // Сбрасываем предыдущие индикаторы ошибок
            postTitleInput.classList.remove('is-invalid');
            postUrlInput.classList.remove('is-invalid');
            if (postContentTextarea) postContentTextarea.classList.remove('is-invalid');

            // !!! ВАЖНО: синхронизируем контент редактора с textarea перед валидацией
            syncEditorContentBeforeValidation();

            // 1. Проверка заголовка
            if (postTitleInput.value.trim() === '') {
                hasErrors = true;
                errors.push('Заголовок обязателен.');
                postTitleInput.classList.add('is-invalid');
            }

            // 2. Проверка URL
            if (postUrlInput.value.trim() === '') {
                hasErrors = true;
                errors.push('URL обязателен.');
                postUrlInput.classList.add('is-invalid');
            }

            // 3. Проверка текста поста
            if (postContentTextarea && !isContentMeaningful(postContentTextarea.value)) {
                hasErrors = true;
                errors.push('Содержание статьи обязательно.');
                postContentTextarea.classList.add('is-invalid');
            }

            // 4. Проверка рубрик (только для постов)
            const articleType = articleTypeInput ? articleTypeInput.value : 'post';
            if (articleType === 'post') {
                const isCategorySelected = Array.from(document.querySelectorAll('input[name="categories[]"]')).some(checkbox => checkbox.checked);
                if (!isCategorySelected) {
                    hasErrors = true;
                    errors.push('Выберите хотя бы одну рубрику.');
                }
            }
            
            if (hasErrors) {
                displayErrors(errors); // Отображаем ошибки в виде блока
                return;
            }
            // *** КОНЕЦ: Клиентская валидация ***

            if (!csrfToken) {
                displayErrors(['Ошибка: CSRF-токен не найден. Перезагрузите страницу.']);
                return;
            }

            const formData = new FormData(postForm);
            formData.append('csrf_token', csrfToken); 
            
            savePostBtn.disabled = true;

            try {
                const response = await fetch(postForm.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                const responseText = await response.text();

                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    // Если не JSON — вероятно, это HTML или чистый текст (например, PHP ошибка)
                    throw new Error(`Сервер вернул ошибку: ${response.status} ${response.statusText}`);
                }

                // Теперь проверяем: если статус не ok, но пришёл JSON — возможно, это наш структурированный ответ об ошибке
                if (!response.ok) {
                    // Если сервер прислал { success: false, message: "..." }
                    if (!data.success && data.message) {
                        if (Array.isArray(data.errors) && data.errors.length > 0)
                        {
                            errorMessages = data.errors.join('\n');
                            alert('Ошибки:\n' + errorMessages);
                        }
                        else {
                            throw new Error(data.message);
                        }
                    } else {
                        // Или хотя бы используем что-то из данных
                        throw new Error(data.message || `HTTP error! status: ${response.status}`);
                    }
                }

                if (data.success) {
                    alert(data.message);
                    window.location.href = data.redirect;
                }
            } catch (error) {
                console.error('Ошибка AJAX:', error);
                alert('Произошла ошибка при создании поста. Пожалуйста, попробуйте снова.');
            } finally {
                savePostBtn.disabled = false;
            }
        });
    }

    /**
     * Асинхронно проверяет доступность слага на сервере.
     * @param {string} slug Проверяемый слаг
     */
    async function checkSlugAvailability(slug) {
        if (slug.trim() === '') {
            setSlugStatus(true, 'URL будет сгенерирован автоматически.');
            return;
        }

        if (!csrfToken) {
            displayErrors(['Ошибка: CSRF-токен не найден.']);
            return;
        }

        try {
            const response = await fetch(`/${adminRoute}/posts/check-url`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    url: slug,
                    csrf_token: csrfToken
                })
            });

            const result = await response.json();
            if (response.ok) {
                if (result.is_unique) {
                    setSlugStatus(true, 'URL доступен.');
                } else {
                    setSlugStatus(false, 'Этот URL уже занят.');
                }
            } else {
                setSlugStatus(false, result.message || 'Ошибка на сервере.');
            }
        } catch (error) {
            console.error('Ошибка при проверке URL:', error);
            setSlugStatus(false, 'Ошибка при проверке URL.');
        }
    }

    /**
     * Обновляет статус поля URL и кнопки сохранения.
     * @param {boolean} isUnique Статус уникальности слага
     * @param {string} message Сообщение для пользователя
     */
    function setSlugStatus(isUnique, message) {
        const postUrlInput = document.getElementById('postUrl');
        const savePostBtn = document.querySelector('button[type="button"]');

        if (!postUrlInput || !savePostBtn) return;
        
        let feedbackDiv = postUrlInput.nextElementSibling;
        if (!feedbackDiv || !feedbackDiv.classList.contains('form-text')) {
            feedbackDiv = document.createElement('div');
            feedbackDiv.classList.add('form-text');
            postUrlInput.parentNode.insertBefore(feedbackDiv, postUrlInput.nextSibling);
        }
        
        feedbackDiv.innerHTML = message;
        
        if (isUnique) {
            postUrlInput.classList.remove('is-invalid');
            postUrlInput.classList.add('is-valid');
            feedbackDiv.classList.remove('text-danger');
            feedbackDiv.classList.add('text-success');
            savePostBtn.disabled = false;
        } else {
            postUrlInput.classList.remove('is-valid');
            postUrlInput.classList.add('is-invalid');
            feedbackDiv.classList.remove('text-success');
            feedbackDiv.classList.add('text-danger');
            savePostBtn.disabled = true;
        }
    }

    // Функция генерации слага
    function generateSlug(text) {
        let slug = text.toString().toLowerCase().trim();
        slug = transliterate(slug);
        slug = slug.replace(/[^a-z0-9-]/g, '-');
        slug = slug.replace(/--+/g, '-');
        slug = slug.replace(/^-+|-+$/g, '');
        return slug;
    }

    // Функция транслитерации
    function transliterate(text) {
        const translitMap = {
            'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo', 'ж': 'zh',
            'з': 'z', 'и': 'i', 'й': 'j', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o',
            'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'c',
            'ч': 'ch', 'ш': 'sh', 'щ': 'shch', 'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu',
            'я': 'ya', 'А': 'A', 'Б': 'B', 'В': 'V', 'Г': 'G', 'Д': 'D', 'Е': 'E', 'Ё': 'Yo',
            'Ж': 'Zh', 'З': 'Z', 'И': 'I', 'Й': 'J', 'К': 'K', 'Л': 'L', 'М': 'M', 'Н': 'N',
            'О': 'O', 'П': 'P', 'Р': 'R', 'С': 'S', 'Т': 'T', 'У': 'U', 'Ф': 'F', 'Х': 'H',
            'Ц': 'C', 'Ч': 'Ch', 'Ш': 'Sh', 'Щ': 'Shch', 'Ъ': '', 'Ы': 'Y', 'Ь': '', 'Э': 'E',
            'Ю': 'Yu', 'Я': 'Ya'
        };
        return text.split('').map(char => translitMap[char] || char).join('');
    }
});