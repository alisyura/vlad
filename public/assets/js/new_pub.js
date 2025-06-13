document.addEventListener("DOMContentLoaded", function () {
    // === 1. Делегирование событий для всех .add-button ===
    document.body.addEventListener('click', function (e) {
        if (e.target.closest('.add-button')) {
            e.preventDefault();
            openModal();
        }
    });

    // === 2. Drag & Drop загрузка файла ===
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('file-upload');
    const uploadTitle = document.getElementById('uploadTitle');
    const formError = document.getElementById('formError');

    const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
    const maxSizeInBytes = 20 * 1024 * 1024; // 20 MB

    // === Клик по области открывает выбор файла ===
    if (uploadArea && fileInput) {
        uploadArea.addEventListener('click', function () {
            fileInput.click();
        });

        uploadArea.addEventListener('dragover', function (e) {
            e.preventDefault(); // Разрешаем drop
            uploadArea.style.backgroundColor = '#ececec';
        });

        uploadArea.addEventListener('dragleave', function (e) {
            e.preventDefault();
            uploadArea.style.backgroundColor = '';
        });

        uploadArea.addEventListener('drop', function (e) {
            e.preventDefault();
            uploadArea.style.backgroundColor = '';

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        });
    }

    // === При выборе файла через клик обновляем заголовок ===
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                handleFile(file);
            }
        });
    }

    function handleFile(file) {
        if (!isValidFileType(file)) {
            showError('Формат файла не поддерживается. Используйте: png, jpeg, jpg, gif');
            return;
        }

        if (!isValidFileSize(file)) {
            showError('Файл слишком большой. Максимальный размер — 20 MB');
            return;
        }

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;

        updateFileName(file);
        hideError();
    }

    function updateFileName(file) {
        if (uploadTitle) {
            uploadTitle.textContent = 'Выбран файл: ' + file.name;
        }
    }

    function isValidFileType(file) {
        return allowedTypes.includes(file.type);
    }

    function isValidFileSize(file) {
        return file.size <= maxSizeInBytes;
    }

    function showError(message) {
        if (formError) {
            formError.textContent = message;
            formError.style.display = 'block';
        }
    }

    function hideError() {
        if (formError) {
            formError.textContent = '';
            formError.style.display = 'none';
        }
    }

    // === 3. Счетчик символов для textarea ===
    const postText = document.getElementById('post-text');
    const charCounter = document.querySelector('.char-counter');

    if (postText && charCounter) {
        charCounter.textContent = `${postText.value.length} / 5000`;

        postText.addEventListener('input', function () {
            const currentLength = this.value.length;
            charCounter.textContent = `${currentLength} / 5000`;
        });
    }

    // === 4. Отправка формы ===
    const publishButton = document.getElementById('publishBtn');

    if (publishButton) {
        publishButton.addEventListener('click', async () => {
            // const email = document.getElementById('email').value.trim();
            const text = document.getElementById('post-text').value.trim();
            const videoLink = document.getElementById('video-link').value.trim();

            const file = fileInput?.files[0] || null;

            // Валидация
            // if (!validateEmail(email)) {
            //     showError('Введите корректный email');
            //     return;
            // }

            if (text.length < 10 || text.length > 5000) {
                showError('Текст должен быть от 10 до 5000 символов');
                return;
            }

            if (file && !isValidFileType(file)) {
                showError('Формат файла не поддерживается. Используйте: png, jpeg, jpg, gif');
                return;
            }

            if (file && !isValidFileSize(file)) {
                showError('Файл слишком большой. Максимальный размер — 20 MB');
                return;
            }

            // Подготовка данных
            const formData = new FormData();
            // formData.append('email', email);
            formData.append('text', text);
            if (videoLink) formData.append('video_link', videoLink);
            if (file) formData.append('image', file);

            try {
                console.error('Вызов сервера /api/publish');
                const response = await fetch('/api/publish', {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) throw new Error('Ошибка сети');
                const result = await response.json();

                console.error('Ответ сервера:\n'+JSON.stringify(result, null, 2));
                showToast('Материал успешно отправлен!');

                closeModal();
                resetForm();

            } catch (error) {
                console.error('Ошибка:', error);
                alert('error '+error);
                showError('Произошла ошибка при отправке. Попробуйте позже.');
            }
        });
    }
});

// === Функция открытия модального окна ===
function openModal() {
    const modal = document.getElementById('addPostModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
    }
}

// === Функция закрытия модального окна ===
window.closeModal = function () {
    const modal = document.getElementById('addPostModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }
};

// === Закрытие кликом вне окна ===
window.addEventListener('click', function (e) {
    const modal = document.getElementById('addPostModal');
    if (e.target === modal) {
        closeModal();
    }
});

// === Очистка формы ===
function resetForm() {
    // document.getElementById('email').value = '';
    document.getElementById('post-text').value = '';
    document.getElementById('video-link').value = '';
    document.getElementById('file-upload').value = '';
    document.querySelector('.char-counter').textContent = '0 / 5000';
    document.getElementById('uploadTitle').textContent = 'Загрузка файла';
    document.getElementById('formError').style.display = 'none';
}




























