class VideoEmbedProcessor {
    constructor() {
        this.modalElement = document.getElementById('videoEmbedModal');
        if (!this.modalElement) return;

        this.inputElement = this.modalElement.querySelector('#video-url');
        this.insertBtn = this.modalElement.querySelector('#video-modal-insert');
        
        // Кнопки закрытия (крестик в шапке и кнопка "Отмена/Закрыть" в футере)
        this.closeButtons = this.modalElement.querySelectorAll('[data-bs-dismiss="modal"], .btn-close, .btn-secondary');
        
        this.bootstrapModal = new bootstrap.Modal(this.modalElement);
        
        this.providers = [
            new YoutubeProvider(),
            new RutubeProvider(),
            new VimeoProvider(),
            new VkProvider(),
            new OkProvider(),
            new MailRuProvider()
        ];

        // Слушаем ввод текста
        this.inputElement.addEventListener('input', () => this.toggleButton());

        // Привязываем закрытие к кнопкам
        this.closeButtons.forEach(btn => {
            btn.addEventListener('click', () => this.close());
        });
    }

    // Проверяем, есть ли подходящий провайдер для введенного текста
    toggleButton() {
        const url = this.inputElement.value.trim();
        const isValid = this.providers.some(p => p.match(url));
        
        // Включаем или выключаем кнопку
        this.insertBtn.disabled = !isValid;
    }

    open(callback) {
        if (!this.bootstrapModal) return;

        this.inputElement.value = ''; 
        this.insertBtn.disabled = true; // Сразу блокируем при открытии
        this.bootstrapModal.show();

        // Очищаем старый обработчик перед установкой нового (чтобы не дублировать вставки)
        this.insertBtn.onclick = () => {
            const url = this.inputElement.value.trim();
            const html = this.getEmbedCode(url);

            if (html) {
                callback(html);
                this.close();
            } else {
                alert('Этот видеохостинг не поддерживается или ссылка неверна');
            }
        };
    }

    close() {
        if (this.bootstrapModal) {
            this.bootstrapModal.hide();
        }
    }

    getEmbedCode(url) {
        for (const provider of this.providers) {
            if (provider.match(url)) return provider.getHtml(url);
        }
        return null;
    }
}