/**
 * Класс для обработки Drag & Drop загрузки файлов.
 */
class DragAndDropHandler {
    constructor(uploadAreaId, fileInputId, uploadTitleId, errorElementId, allowedTypes = [], maxSizeInBytes = 20 * 1024 * 1024) {
        this.uploadArea = document.getElementById(uploadAreaId);
        this.fileInput = document.getElementById(fileInputId);
        this.uploadTitle = document.getElementById(uploadTitleId);
        this.errorElement = document.getElementById(errorElementId);
        this.allowedTypes = allowedTypes;
        this.maxSizeInBytes = maxSizeInBytes;

        if (this.uploadArea && this.fileInput) {
            this.init();
        }
    }

    init() {
        this.uploadArea.addEventListener('click', () => this.fileInput.click());
        this.uploadArea.addEventListener('dragover', (e) => this.handleDragOver(e));
        this.uploadArea.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        this.uploadArea.addEventListener('drop', (e) => this.handleDrop(e));
        this.fileInput.addEventListener('change', (e) => this.handleChange(e));
    }

    handleDragOver(e) {
        e.preventDefault();
        this.uploadArea.style.backgroundColor = '#ececec';
        this.hideError();
    }

    handleDragLeave(e) {
        e.preventDefault();
        this.uploadArea.style.backgroundColor = '';
    }

    handleDrop(e) {
        e.preventDefault();
        this.uploadArea.style.backgroundColor = '';
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            this.handleFile(files[0]);
        }
    }

    handleChange() {
        const file = this.fileInput.files[0];
        if (file) {
            this.handleFile(file);
        }
    }

    handleFile(file) {
        if (!this.isValidFileType(file)) {
            this.showError('Формат файла не поддерживается. Используйте: png, jpeg, jpg, gif');
            return;
        }

        if (!this.isValidFileSize(file)) {
            this.showError(`Файл слишком большой. Максимальный размер — ${this.maxSizeInBytes/1024/1024} MB`);
            return;
        }

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        this.fileInput.files = dataTransfer.files;

        this.updateFileName(file);
        this.hideError();
    }

    isValidFileType(file) {
        return this.allowedTypes.includes(file.type);
    }

    isValidFileSize(file) {
        return file.size <= this.maxSizeInBytes;
    }

    updateFileName(file) {
        if (this.uploadTitle) {
            this.uploadTitle.textContent = `Выбран файл: ${file.name}`;
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

    reset() {
        if (this.fileInput) {
            this.fileInput.value = '';
        }
        if (this.uploadTitle) {
            this.uploadTitle.textContent = 'Загрузка файла';
        }
        this.hideError();
    }
}
