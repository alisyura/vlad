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
    const postText = document.getElementById('msg-text');
    const charCounter = document.querySelector('.contact-char-counter');

    if (postText && charCounter) {
        charCounter.textContent = `${postText.value.length} / 5000`;

        postText.addEventListener('input', function () {
            const currentLength = this.value.length;
            charCounter.textContent = `${currentLength} / 5000`;
        });
    }
});