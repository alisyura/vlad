/**
 * Главный класс, управляющий всей формой.
 */
class ContactFormManager {
    constructor(formConfig) {
        this.config = formConfig;
        this.form = document.getElementById(this.config.formId);
        this.sendBtn = document.getElementById(this.config.sendBtnId);
        this.errorElement = document.getElementById(this.config.errorElementId);
        this.overlay = document.getElementById(this.config.loadingOverlayId);

        this.dragAndDropHandler = new DragAndDropHandler(
            this.config.uploadAreaId,
            this.config.fileInputId,
            this.config.uploadTitleId,
            this.config.errorElementId,
            this.config.allowedFileTypes,
            this.config.maxFileSize
        );

        this.charCounter = new CharCounter(this.config.textareaId, this.config.charCounterSelector);

        if (this.sendBtn) {
            this.init();
        }
    }

    init() {
        this.sendBtn.addEventListener('click', (e) => this.handleFormSubmit(e));
    }

    // Показать оверлей
    showLoadingOverlay() {
        if (this.overlay) {
            this.overlay.style.display = 'flex';
            // Блокируем прокрутку, если нужно
            document.body.style.overflow = 'hidden'; 
        }
    }

    // Скрыть оверлей
    hideLoadingOverlay() {
        if (this.overlay) {
            this.overlay.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        this.hideError();

        const formData = this.collectFormData();

        if (!this.validateFormData(formData)) {
            return;
        }

        try {
            this.showLoadingOverlay();
            
            const csrfToken = await getFreshCsrfToken();
            if (!csrfToken) {
                showToast('Не удалось получить токен, попробуйте снова.');
                this.hideLoadingOverlay();
                return;
            }

            const fetchPromise = fetch(this.config.apiUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData,
            });

            // Промис для минимальной видимости оверлея (400 мс)
            const minTimePromise = delay(400);
            // Ждем, пока оба промиса завершатся. [response] берется из первого промиса.
            const [response] = await Promise.all([fetchPromise, minTimePromise]);

            // if (!response.ok) {
            //     throw new Error('Ошибка сети');
            // }

            const result = await response.json();

            this.hideLoadingOverlay();

            if (result.success) {
                showToast('Ваше сообщение успешно отправлено!');
                this.resetForm();
            } else {
                let errorMessages = Array.isArray(result.errors) ? result.errors : [result.errors || 'Неизвестная ошибка'];
                const errorMessage = 'Сообщение не отправлено\n\n' + errorMessages.join('\n');
                showToast(errorMessage);
            }
        } catch (error) {
            console.error('Ошибка:', error);
            this.showError('Произошла ошибка при отправке. Попробуйте позже.');
        } finally {
            this.hideLoadingOverlay();
        }
    }

    collectFormData() {
        const formData = new FormData();
        const kontaktMsgName = document.getElementById('kontaktMsgName').value.trim();
        const kontaktMsgEmail = document.getElementById('kontaktMsgEmail').value.trim();
        const kontaktMsgTitle = document.getElementById('kontaktMsgTitle').value.trim();
        const kontaktMsgText = document.getElementById('kontaktMsgText').value.trim();
        const contactFile = this.dragAndDropHandler.fileInput?.files[0] || null;

        formData.append('name', kontaktMsgName);
        formData.append('email', kontaktMsgEmail);
        formData.append('title', kontaktMsgTitle);
        formData.append('text', kontaktMsgText);
        if (contactFile) {
            formData.append('image', contactFile);
        }

        return formData;
    }

    validateFormData(formData) {
        const name = formData.get('name');
        const email = formData.get('email');
        const text = formData.get('text');
        const title = formData.get('title');
        const file = this.dragAndDropHandler.fileInput?.files[0] || null;

        if (!this.validateEmail(email)) {
            this.showError('Введите корректный email');
            return false;
        }

        if (name.length === 0) {
            this.showError('Введите имя');
            return false;
        }

        if (text.length < 10 || text.length > 5000) {
            this.showError('Текст должен быть от 10 до 5000 символов');
            return false;
        }

        if (title.length === 0) {
            this.showError('Введите тему сообщения');
            return false;
        }

        if (file && !this.dragAndDropHandler.isValidFileType(file)) {
            this.showError('Формат файла не поддерживается. Используйте: png, jpeg, jpg, gif');
            return false;
        }

        if (file && !this.dragAndDropHandler.isValidFileSize(file)) {
            this.showError(`Файл слишком большой. Максимальный размер — ${this.config.maxFileSize/1024/1024} MB`);
            return false;
        }

        return true;
    }

    validateEmail(email) {
        const re = /\S+@\S+\.\S+/;
        return re.test(email);
    }

    showError(message) {
        if (this.errorElement) {
            this.errorElement.textContent = message;
            this.errorElement.style.display = 'block';
        }
    }

    hideError() {
        if (this.errorElement) {
            this.errorElement.textContent = '';
            this.errorElement.style.display = 'none';
        }
    }

    resetForm() {
        document.getElementById('kontaktMsgName').value = '';
        document.getElementById('kontaktMsgEmail').value = '';
        document.getElementById('kontaktMsgTitle').value = '';
        document.getElementById('kontaktMsgText').value = '';
        this.dragAndDropHandler.reset();
        this.charCounter.updateCounter();
        this.hideError();
    }
}

// Инициализация
document.addEventListener("DOMContentLoaded", function () {
    const maxFileSizeInput = document.getElementById('file-upload-max_filesize');
    const maxFileSize = maxFileSizeInput ? parseInt(maxFileSizeInput.value, 10) : 0;

    const config = {
        formId: 'contact-form',
        sendBtnId: 'sendBtn',
        errorElementId: 'contactFormError',
        uploadAreaId: 'contactUploadArea',
        fileInputId: 'contact-file-upload',
        uploadTitleId: 'contactUploadTitle',
        textareaId: 'kontaktMsgText',
        loadingOverlayId: 'loadingOverlay',
        charCounterSelector: '.contact-char-counter',
        apiUrl: '/api/send_msg',
        allowedFileTypes: ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'],
        maxFileSize: maxFileSize
    };

    new ContactFormManager(config);
});

