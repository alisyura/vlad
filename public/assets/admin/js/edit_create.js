document.addEventListener('DOMContentLoaded', function() {
    // генерация урла из заголовка поста
    const postTitleInput = document.getElementById('postTitle');
    const postUrlInput = document.getElementById('postUrl');
    let slugTimeout = null;
    const savePostBtn = document.querySelector('button[type="submit"]');

    let slugCheckTimeout = null;

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
                }, 2500); // Задержка в 2,5 секунды
            }
        });
    }

    if (postUrlInput) {
        postUrlInput.addEventListener('input', function() {
            let value = this.value;

            // Транслитерация русских букв
            const translitMap = {
                'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo', 'ж': 'zh',
                'з': 'z', 'и': 'i', 'й': 'j', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o',
                'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'c',
                'ч': 'ch', 'ш': 'sh', 'щ': 'shch', 'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya',
                'А': 'A', 'Б': 'B', 'В': 'V', 'Г': 'G', 'Д': 'D', 'Е': 'E', 'Ё': 'YO', 'Ж': 'ZH',
                'З': 'Z', 'И': 'I', 'Й': 'J', 'К': 'K', 'Л': 'L', 'М': 'M', 'Н': 'N', 'О': 'O',
                'П': 'P', 'Р': 'R', 'С': 'S', 'Т': 'T', 'У': 'U', 'Ф': 'F', 'Х': 'H', 'Ц': 'C',
                'Ч': 'CH', 'Ш': 'SH', 'Щ': 'SHCH', 'Ъ': '', 'Ы': 'Y', 'Ь': '', 'Э': 'E', 'Ю': 'YU', 'Я': 'YA'
            };

            // Шаг 1: Заменяем русские символы
            let newValue = value
                .split('')
                .map(char => translitMap[char] || char)
                .join('');

            // Шаг 2: Оставляем только разрешённые символы: a-z, 0-9, -, _, пробелы → в дефисы
            newValue = newValue.replace(/[^a-zA-Z0-9_\-\s]/g, '-'); // спецсимволы → дефис
            newValue = newValue.replace(/[\s]+/g, '-');              // пробелы → дефисы
            newValue = newValue.replace(/-+/g, '-');                 // множественные дефисы → один
            newValue = newValue.replace(/^-+|-+$/g, '');             // удаляем дефисы в начале и конце

            // Шаг 3: Если значение изменилось — обновляем поле
            if (newValue !== value) {
                this.value = newValue;
            }


            if (slugCheckTimeout) {
                clearTimeout(slugCheckTimeout);
            }

            slugCheckTimeout = setTimeout(() => {
                const slug = this.value;
                checkSlugAvailability(slug);
            }, 500); // Задержка в 500 мс
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

        const adminRoute = document.getElementById('adminRoute').value;
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;

        try {
            const response = await fetch(`/${adminRoute}/posts/check-url`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
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
                setSlugStatus(false, 'Ошибка на сервере.');
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
        const feedbackDiv = postUrlInput.nextElementSibling;
        
        feedbackDiv.innerHTML = message;
        
        if (isUnique) {
            // Устанавливаем светло-зеленую рамку и зелёный текст
            postUrlInput.classList.remove('is-invalid');
            postUrlInput.classList.add('is-valid');
            feedbackDiv.classList.remove('text-danger');
            feedbackDiv.classList.add('text-success');
            savePostBtn.disabled = false;
        } else {
            // Устанавливаем светло-красную рамку и красный текст
            postUrlInput.classList.remove('is-valid');
            postUrlInput.classList.add('is-invalid');
            feedbackDiv.classList.remove('text-success');
            feedbackDiv.classList.add('text-danger');
            savePostBtn.disabled = true;
        }
    }

    /**
     * Генерирует URL-слаг из строки, очищая её и переводя в нижний регистр.
     * @param {string} str Исходная строка
     * @returns {string} Готовый слаг
     */
    function generateSlug(str) {
        const translitMap = {
            'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo', 'ж': 'zh',
            'з': 'z', 'и': 'i', 'й': 'j', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o',
            'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'c',
            'ч': 'ch', 'ш': 'sh', 'щ': 'shch', 'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu',
            'я': 'ya',
            'А': 'A', 'Б': 'B', 'В': 'V', 'Г': 'G', 'Д': 'D', 'Е': 'E', 'Ё': 'YO', 'Ж': 'ZH',
            'З': 'Z', 'И': 'I', 'Й': 'J', 'К': 'K', 'Л': 'L', 'М': 'M', 'Н': 'N', 'О': 'O',
            'П': 'P', 'Р': 'R', 'С': 'S', 'Т': 'T', 'У': 'U', 'Ф': 'F', 'Х': 'H', 'Ц': 'C',
            'Ч': 'CH', 'Ш': 'SH', 'Щ': 'SHCH', 'Ъ': '', 'Ы': 'Y', 'Ь': '', 'Э': 'E', 'Ю': 'YU',
            'Я': 'YA',
        };

        let slug = str.toLowerCase();

        // Заменяем символы по карте транслитерации
        slug = slug.split('').map(char => translitMap[char] || char).join('');

        // Удаляем все, кроме букв, цифр, пробелов, подчеркиваний и дефисов
        slug = slug.replace(/[^a-z0-9\s-_]/g, ''); 
        
        // Заменяем пробелы и дефисы на один дефис
        slug = slug.replace(/[\s-]+/g, '-');
        
        // Удаляем дефисы в начале и конце
        slug = slug.replace(/^-+|-+$/g, '');

        return slug;
    }
});