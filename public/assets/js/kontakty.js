document.addEventListener("DOMContentLoaded", function () {
    // === 2. Drag & Drop загрузка файла ===
    const contactUploadArea = document.getElementById('contactUploadArea');
    const contactFileInput = document.getElementById('contact-file-upload');
    const contactUploadTitle = document.getElementById('contactUploadTitle');
    const contactFormError = document.getElementById('contactFormError');

    const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
    const maxSizeInBytes = 20 * 1024 * 1024; // 20 MB

    // === Клик по области открывает выбор файла ===
    if (contactUploadArea && contactFileInput) {
        contactUploadArea.addEventListener('click', function () {
            contactFileInput.click();
        });

        contactUploadArea.addEventListener('dragover', function (e) {
            e.preventDefault(); // Разрешаем drop
            contactUploadArea.style.backgroundColor = '#ececec';
        });

        contactUploadArea.addEventListener('dragleave', function (e) {
            e.preventDefault();
            contactUploadArea.style.backgroundColor = '';
        });

        contactUploadArea.addEventListener('drop', function (e) {
            e.preventDefault();
            contactUploadArea.style.backgroundColor = '';

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                contactHandleFile(files[0]);
            }
        });
    }

    // === При выборе файла через клик обновляем заголовок ===
    if (contactFileInput) {
        contactFileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                contactHandleFile(file);
            }
        });
    }

    function contactHandleFile(file) {
        if (!contactIsValidFileType(file)) {
            contactShowError('Формат файла не поддерживается. Используйте: png, jpeg, jpg, gif');
            return;
        }

        if (!contactIsValidFileSize(file)) {
            contactShowError('Файл слишком большой. Максимальный размер — 20 MB');
            return;
        }

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        contactFileInput.files = dataTransfer.files;

        contactUpdateFileName(file);
        contactHideError();
    }

    function contactUpdateFileName(file) {
        if (contactUploadTitle) {
            contactUploadTitle.textContent = 'Выбран файл: ' + file.name;
        }
    }

    function contactIsValidFileType(file) {
        return allowedTypes.includes(file.type);
    }

    function contactIsValidFileSize(file) {
        return file.size <= maxSizeInBytes;
    }

    function contactShowError(message) {
        if (contactFormError) {
            contactFormError.textContent = message;
            contactFormError.style.display = 'block';
        }
    }

    function contactHideError() {
        if (contactFormError) {
            contactFormError.textContent = '';
            contactFormError.style.display = 'none';
        }
    }


    // === 3. Счетчик символов для textarea ===
    const postText = document.getElementById('kontaktMsgText');
    const charCounter = document.querySelector('.contact-char-counter');

    if (postText && charCounter) {
        charCounter.textContent = `${postText.value.length} / 5000`;

        postText.addEventListener('input', function () {
            const currentLength = this.value.length;
            charCounter.textContent = `${currentLength} / 5000`;
        });
    }

    // === 4. Отправка формы ===
    const sendBtn = document.getElementById('sendBtn');

    if (sendBtn) {
        sendBtn.addEventListener('click', async () => {
            const kontaktMsgName = document.getElementById('kontaktMsgName').value.trim();
            const kontaktMsgEmail = document.getElementById('kontaktMsgEmail').value.trim();
            const kontaktMsgTitle = document.getElementById('kontaktMsgTitle').value.trim();
            const kontaktMsgText = document.getElementById('kontaktMsgText').value.trim();

            const contactFile = contactFileInput?.files[0] || null;

            // Валидация
            if (!validateEmail(kontaktMsgEmail)) {
                contactShowError('Введите корректный email');
                return;
            }

            if (kontaktMsgName.length === 0) {
                contactShowError('Введите имя');
                return;
            }

            if (kontaktMsgText.length < 10 || kontaktMsgText.length > 5000) {
                contactShowError('Текст должен быть от 10 до 5000 символов');
                return;
            }

            if (kontaktMsgTitle.length === 0) {
                contactShowError('Введите тему сообщения');
                return;
            }

            if (contactFile && !contactIsValidFileType(contactFile)) {
                contactShowError('Формат файла не поддерживается. Используйте: png, jpeg, jpg, gif');
                return;
            }

            if (contactFile && !contactIsValidFileSize(contactFile)) {
                contactShowError('Файл слишком большой. Максимальный размер — 20 MB');
                return;
            }

            // Подготовка данных
            const formData = new FormData();
            formData.append('name', kontaktMsgName);
            formData.append('email', kontaktMsgEmail);
            formData.append('title', kontaktMsgTitle);
            formData.append('text', kontaktMsgText);
            if (contactFile) formData.append('image', contactFile);

            getFormData(formData);
            
            try {
                //console.error('Вызов сервера /api/send_msg');
                const response = await fetch('/api/send_msg', {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) throw new Error('Ошибка сети');
                const result = await response.json();

                if (result.success) {
                    //console.error('Ответ сервера:\n' + JSON.stringify(result, null, 2));
                    showToast('Ваше сообщение успешно отправлено!');
                    resetContactForm();
                } else {
                    // Формируем текст ошибок из массива `message`
                    let errorMessages = [];

                    if (Array.isArray(result.message)) {
                        errorMessages = result.message;
                    } else if (typeof result.message === 'string') {
                        errorMessages = [result.message];
                    } else {
                        errorMessages = ['Неизвестная ошибка'];
                    }

                    const errorMessage = 'Сообщение не отправлено\n\n' + errorMessages.join('\n');
                    showToast(errorMessage);
                }

            } catch (error) {
                console.error('Ошибка:', error);
                contactShowError('Произошла ошибка при отправке. Попробуйте позже.');
            }
        });
    }
});

// === Очистка формы ===
function resetContactForm() {
    // document.getElementById('email').value = '';
    document.getElementById('kontaktMsgName').value = '';
    document.getElementById('kontaktMsgEmail').value = '';
    document.getElementById('kontaktMsgTitle').value = '';
    document.getElementById('kontaktMsgText').value = '';
    document.getElementById('contact-file-upload').value = '';
    document.querySelector('.contact-char-counter').textContent = '0 / 5000';
    document.getElementById('contactUploadTitle').textContent = 'Загрузка файла';
    document.getElementById('contactFormError').style.display = 'none';
}