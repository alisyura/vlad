class TaxonomyDashboard {
    constructor(taxonomyType) {
        this.taxonomyType = taxonomyType;

        // Получаем ссылки на DOM-элементы
        this.createTaxonomyForm = document.getElementById('create-taxonomy-form');
        this.editTaxonomyForm = document.getElementById('edit-taxonomy-form'); 
        this.actionLinksContainer = document.querySelector('.table tbody');
        const actionModalElement = document.getElementById('actionModal');
        if (actionModalElement) {
            this.actionModal = new bootstrap.Modal(actionModalElement);
            this.modalTitle = document.getElementById('actionModalLabel');
            this.modalBody = document.getElementById('actionModalBody');
            this.confirmActionButton = document.getElementById('confirmActionButton');
        } else {
            // Если элемент не найден, присваиваем null, чтобы не было ошибки
            this.actionModal = null;
            this.modalTitle = null;
            this.modalBody = null;
            this.confirmActionButton = null;
        }

        this.urlInput = document.getElementById('url');

        this.editNameInput = document.getElementById('name');
        this.editUrlInput = document.getElementById('url');

        // Переменные для хранения данных действия
        this.taxonomyIdToActOn = null;
        this.actionToPerform = null;

        // Инициализируем обработчики событий
        this.initEventListeners();
    }

    initEventListeners() {
        // Обработчик для создания тэга
        if (this.createTaxonomyForm) {
            this.createTaxonomyForm.querySelector('button[type="button"]').addEventListener('click', this.handleCreateTaxonomySubmit.bind(this));
        }

        // Обработчик для редактирования тэга
        if (this.editTaxonomyForm) {
            this.editTaxonomyForm.querySelector('button[type="button"]').addEventListener('click', this.handleEditTaxonomySubmit.bind(this));
        }

        // Обработчик кликов по таблице (для 'edit', 'block', 'unblock', 'delete')
        if (this.actionLinksContainer) {
            this.actionLinksContainer.addEventListener('click', this.handleActionClick.bind(this));
        }
        
        // Обработчик подтверждения в модальном окне
        if (this.confirmActionButton) {
            this.confirmActionButton.addEventListener('click', this.handleModalConfirm.bind(this));
        }

        // Обработчик ввода в поле логина для формы создания
        if (this.urlInput) {
            this.urlInput.addEventListener('input', this.handleUrlInput.bind(this));
        }
    }

    // Создание нового тэга. Обработка отправки формы
    async handleCreateTaxonomySubmit(e) {
        e.preventDefault();

        this.urlInput.value = this.transliterate(this.urlInput.value);

        const formData = new FormData(this.createTaxonomyForm);
        const data = Object.fromEntries(formData.entries());

        // Проверяем, что поля 'name' и 'url' не пустые
        if (!data.name || !data.url) {
            // Если одно из полей пустое, выводим сообщение об ошибке
            alert('Пожалуйста, заполните все поля.');
            return; // Останавливаем выполнение дальнейшего кода
        }

        const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content;
        if (!csrfToken) {
            alert('Ошибка: CSRF-токен не найден.');
            return;
        }

        try {
            const response = await fetch(`/${adminRoute}/taxonomy/${this.taxonomyType}/api/create`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (!response.ok) {
                if (response.status === 401)
                {
                    // Пользователь не авторизован, перенаправляем на страницу логина
                    window.location.href = `/${adminRoute}/login`;
                }
                if (!result.success && result.message) {
                    alert('Ошибка выполнения операции:\n' + result.message);
                } else {
                    throw new Error(data.message);
                }

                return;
            }

            if (result.success) {
                alert('Тэг успешно создан!');
                this.createTaxonomyForm.reset();
                window.location.reload(); 
            } else {
                alert('Ошибка: ' + result.message);
            }
        } catch (error) {
            console.error('Ошибка:', error);
            alert('Произошла ошибка при создании тэга.');
        }
    }


    // НОВОЕ: Обработчик отправки формы редактирования
    async handleEditTaxonomySubmit(e) {
        e.preventDefault();
    
        const formData = new FormData(this.editTaxonomyForm);
        const data = Object.fromEntries(formData.entries());
    
        // Проверяем, что поля 'name' не пустое
        if (!data.name) {
            // Если одно из полей пустое, выводим сообщение об ошибке
            alert('Пожалуйста, заполните все поля.');
            return; // Останавливаем выполнение дальнейшего кода
        }
    
        const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content;
        if (!csrfToken) {
            alert('Ошибка: CSRF-токен не найден.');
            return;
        }
    
        try {
            const response = await fetch(`/${adminRoute}/taxonomy/${this.taxonomyType}/api/edit/${data.taxonomy_id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });
    
            const result = await response.json();
    
            if (!response.ok) {
                if (response.status === 401) {
                    window.location.href = `/${adminRoute}/login`;
                }
                alert('Ошибка выполнения операции:\n' + (result.message || 'Неизвестная ошибка.'));
                return;
            }
    
            if (result.success) {
                alert('Тэг успешно обновлен!');
                window.location.href = `/${adminRoute}/taxonomy/${this.taxonomyType}`;
            } else {
                alert('Ошибка: ' + result.message);
            }
    
        } catch (error) {
            console.error('Ошибка:', error);
            alert('Произошла ошибка при обновлении тэга.');
        }
    }

    // НОВОЕ: Обрабатываем ввод в поле Урла
    handleUrlInput() {
        this.urlInput.value = this.transliterate(this.urlInput.value);
    }
    
    // Метод для транслитерации
    transliterate(text) {
        const translitMap = {
            'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'e', 'ж': 'zh', 'з': 'z',
            'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r',
            'с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'c', 'ч': 'ch', 'ш': 'sh', 'щ': 'sch',
            'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya'
        };

        // Заменяем русские буквы, приводим к нижнему регистру и заменяем пробелы на дефисы
        return text
            .toLowerCase()
            .split('')
            .map(char => translitMap[char] || char)
            .join('')
            .replace(/\s/g, '-') // Заменяем пробелы на дефисы
            .replace(/[^a-z0-9-]/g, '') // Удаляем все, кроме букв, цифр и дефисов
            .replace(/--+/g, '-'); // Убираем повторяющиеся дефисы
    }

    // Обработка клика по ссылкам действий
    handleActionClick(e) {
        const link = e.target.closest('.action-link');
        if (!link) return;

        e.preventDefault();
        
        this.taxonomyIdToActOn = link.dataset.id;
        this.actionToPerform = link.dataset.action;

        if (!this.actionModal)
        {
            return;
        }

        let title = '';
        let bodyText = '';
        let confirmBtnText = '';

        switch (this.actionToPerform) {
            case 'delete':
                title = 'Подтвердить удаление';
                bodyText = 'Вы уверены, что хотите удалить тэг? Это действие необратимо.';
                confirmBtnText = 'Удалить';
                this.confirmActionButton.className = 'btn btn-danger';
                break;
            default:
                return;
        }

        this.modalTitle.textContent = title;
        this.modalBody.textContent = bodyText;
        this.confirmActionButton.textContent = confirmBtnText;

        this.actionModal.show();
    }

    // Обработка подтверждения в модальном окне
    async handleModalConfirm() {
        this.actionModal.hide();
        
        const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content;
        if (!csrfToken) {
            alert('Ошибка: CSRF-токен не найден.');
            return;
        }

        let method = '';
        switch (this.actionToPerform) {
            case 'delete':
                method = 'DELETE';
                break;
            default:
                return;
        }

        
        try {
            const response = await fetch(`/${adminRoute}/taxonomy/${this.taxonomyType}/api/${this.actionToPerform}/${this.taxonomyIdToActOn}`, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
            });

            const result = await response.json();

            if (!response.ok) {
                if (response.status === 401)
                {
                    // Пользователь не авторизован, перенаправляем на страницу логина
                    window.location.href = `/${adminRoute}/login`;
                }
                if (!result.success && result.message) {
                    alert('Ошибка выполнения операции:\n' + result.message);
                } else {
                    throw new Error(data.message);
                }
                return;
            }

            if (result.success) {
                //alert('Действие выполнено успешно!');
                window.location.reload(); 
            } else {
                alert('Ошибка: ' + result.message);
            }

        } catch (error) {
            console.error('Ошибка:', error);
            alert('Произошла ошибка при выполнении действия.');
        } finally {
            this.taxonomyIdToActOn = null;
            this.actionToPerform = null;
        }
    }
}

// Создаём и запускаем экземпляр класса при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    // Получаем путь из URL
    const path = window.location.pathname; // например: /eryfbh/taxonomy/tags или /eryfbh/taxonomy/tags/p3
    
    // Разбиваем путь на части
    const parts = path.split('/').filter(part => part !== '');
    // parts = ['eryfbh', 'taxonomy', 'tags'] или ['eryfbh', 'taxonomy', 'tags', 'p3']
    
    // Получаем второй элемент (индекс 1) - это и есть нужное значение
    const taxonomyType = parts[2]; // 'tags' или другое значение
    
    console.log('Taxonomy type:', taxonomyType);

    new TaxonomyDashboard(taxonomyType);
});