// new_pub.js
// Класс для управления модальным окном.
class ModalController {
    constructor(modalId) {
        this.modal = document.getElementById(modalId);

        if (this.modal) {
            this.init();
        }
    }

    init() {
        document.body.addEventListener('click', (e) => {
            if (e.target.closest('.add-button')) {
                e.preventDefault();
                this.openModal();
            }
        });

        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.closeModal();
            }
        });

        window.closeModal = this.closeModal.bind(this);
    }

    openModal() {
        if (this.modal) {
            this.modal.style.display = 'flex';
            document.body.classList.add('modal-open');
        }
    }

    closeModal() {
        if (this.modal) {
            this.modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    }
}

// Класс для управления формой публикации, использующий классы из kontakty.js
class PublishFormManager {
    constructor(config) {
        this.config = config;
        this.publishButton = document.getElementById(this.config.publishBtnId);
        this.errorElement = document.getElementById(this.config.errorElementId);
        this.fileInput = document.getElementById(this.config.fileInputId);
        this.postText = document.getElementById(this.config.textareaId);
        this.videoLink = document.getElementById(this.config.videoLinkInputId);

        if (this.publishButton) {
            this.init();
        }
    }

    init() {
        this.dragAndDropHandler = new DragAndDropHandler(
            this.config.uploadAreaId,
            this.config.fileInputId,
            this.config.uploadTitleId,
            this.config.errorElementId,
            this.config.allowedFileTypes,
            this.config.maxFileSize
        );
        this.charCounter = new CharCounter(this.config.textareaId, this.config.charCounterSelector);
        
        this.publishButton.addEventListener('click', this.handleFormSubmit.bind(this));
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        this.hideError();

        const file = this.fileInput?.files[0] || null;

        const text = this.postText.value.trim();
        if (text.length < 10 || text.length > 5000) {
            this.showError('Текст должен быть от 10 до 5000 символов');
            return;
        }
        if (file && !this.dragAndDropHandler.isValidFileType(file)) {
            this.showError('Формат файла не поддерживается. Используйте: png, jpeg, jpg, gif');
            return;
        }
        if (file && !this.dragAndDropHandler.isValidFileSize(file)) {
            this.showError(`Файл слишком большой. Максимальный размер — ${this.config.maxFileSize / (1024 * 1024)} MB`);
            return;
        }

        const formData = new FormData();
        formData.append('text', text);
        if (this.videoLink.value.trim()) {
            formData.append('video_link', this.videoLink.value.trim());
        }
        if (file) {
            formData.append('image', file);
        }

        try {
            const csrfToken = await getFreshCsrfToken();
            if (!csrfToken) {
                showToast('Не удалось получить токен, попробуйте снова.');
                return;
            }

            const response = await fetch(this.config.apiUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData,
            });

            if (!response.ok) {
                // Проверяем HTTP-статус
                if (response.status === 400) {
                    // Это предсказуемая ошибка, например, связанная с валидацией
                    // Используем сообщение, полученное от сервера
                    const result = await response.json();
                    this.showError(result.message || 'Ошибка валидации');
                    return;
                } else {
                    // Это непредвиденная ошибка (5xx или другие)
                    // Используем общее сообщение
                    throw new Error('Произошла ошибка при отправке. Попробуйте позже.');
                }
            }

            const result = await response.json();

            if (result.success) {
                showToast('Материал успешно отправлен!');
                this.resetForm();
                if (window.closeModal) {
                    window.closeModal();
                }
            } else {
                const errorMessages = Array.isArray(result.message) ? result.message : [result.message || 'Неизвестная ошибка'];
                this.showError(errorMessages.join('\n'));
            }

        } catch (error) {
            console.error('Ошибка:', error);
            this.showError('Произошла ошибка при отправке. Попробуйте позже.');
        }
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
        this.postText.value = '';
        this.videoLink.value = '';
        this.dragAndDropHandler.reset();
        this.charCounter.updateCounter();
        this.hideError();
    }
}

// Инициализация на загрузке страницы
document.addEventListener("DOMContentLoaded", function () {
    const maxFileSizeInput = document.getElementById('file-upload-max_filesize'); // Это поле нужно будет добавить на страницу с формой публикации
    const maxFileSize = maxFileSizeInput ? parseInt(maxFileSizeInput.value, 10) : 20 * 1024 * 1024;

    const config = {
        modalId: 'addPostModal',
        publishBtnId: 'publishBtn',
        errorElementId: 'formError',
        uploadAreaId: 'uploadArea',
        fileInputId: 'file-upload',
        uploadTitleId: 'uploadTitle',
        textareaId: 'post-text',
        videoLinkInputId: 'video-link',
        charCounterSelector: '.char-counter',
        apiUrl: '/api/publish',
        allowedFileTypes: ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'],
        maxFileSize: maxFileSize,
    };

    new ModalController(config.modalId);
    new PublishFormManager(config);
});
