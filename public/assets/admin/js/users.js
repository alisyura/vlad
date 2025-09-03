class UserDashboard {
    constructor() {
        // Получаем ссылки на DOM-элементы
        this.createUserForm = document.getElementById('create-user-form');
        this.actionLinksContainer = document.querySelector('.table tbody');
        this.actionModal = new bootstrap.Modal(document.getElementById('actionModal'));
        this.modalTitle = document.getElementById('actionModalLabel');
        this.modalBody = document.getElementById('actionModalBody');
        this.confirmActionButton = document.getElementById('confirmActionButton');
        this.loginInput = document.getElementById('login');

        // Переменные для хранения данных действия
        this.userIdToActOn = null;
        this.actionToPerform = null;

        // Инициализируем обработчики событий
        this.initEventListeners();
    }

    initEventListeners() {
        if (this.createUserForm) {
            this.createUserForm.querySelector('button[type="button"]').addEventListener('click', this.handleFormSubmit.bind(this));
        }

        if (this.actionLinksContainer) {
            this.actionLinksContainer.addEventListener('click', this.handleActionClick.bind(this));
        }
        
        if (this.confirmActionButton) {
            this.confirmActionButton.addEventListener('click', this.handleModalConfirm.bind(this));
        }

        if (this.loginInput) {
            this.loginInput.addEventListener('input', this.handleLoginInput.bind(this));
        }
    }

    // Создание нового пользователя. Обработка отправки формы
    async handleFormSubmit(e) {
        e.preventDefault();

        this.loginInput.value = this.transliterate(this.loginInput.value);

        const formData = new FormData(this.createUserForm);
        const data = Object.fromEntries(formData.entries());

        if (data.password !== data.confirm_password) {
            alert('Пароли не совпадают!');
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content;
        if (!csrfToken) {
            alert('Ошибка: CSRF-токен не найден.');
            return;
        }

        try {
            const response = await fetch(`/${adminRoute}/users/api/create`, {
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
                alert('Пользователь успешно создан!');
                this.createUserForm.reset();
                window.location.reload(); 
            } else {
                alert('Ошибка: ' + result.message);
            }
        } catch (error) {
            console.error('Ошибка:', error);
            alert('Произошла ошибка при создании пользователя.');
        }
    }

    // НОВОЕ: Обрабатываем ввод в поле логина
    handleLoginInput() {
        this.loginInput.value = this.transliterate(this.loginInput.value);
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
        
        this.userIdToActOn = link.dataset.id;
        this.actionToPerform = link.dataset.action;

        let title = '';
        let bodyText = '';
        let confirmBtnText = '';

        switch (this.actionToPerform) {
            case 'block':
                title = 'Подтвердить блокировку';
                bodyText = 'Вы уверены, что хотите заблокировать этого пользователя?';
                confirmBtnText = 'Заблокировать';
                this.confirmActionButton.className = 'btn btn-danger';
                break;
            case 'unblock':
                title = 'Подтвердить разблокировку';
                bodyText = 'Вы уверены, что хотите разблокировать этого пользователя?';
                confirmBtnText = 'Разблокировать';
                this.confirmActionButton.className = 'btn btn-success';
                break;
            case 'delete':
                title = 'Подтвердить удаление';
                bodyText = 'Вы уверены, что хотите удалить этого пользователя? Это действие необратимо.';
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
            case 'block':
            case 'unblock':
                method = 'PATCH';
                break;
            case 'delete':
                method = 'DELETE';
                break;
            default:
                return;
        }

        
        try {
            const response = await fetch(`/${adminRoute}/users/api/${this.actionToPerform}/${this.userIdToActOn}`, {
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
            this.userIdToActOn = null;
            this.actionToPerform = null;
        }
    }
}

// Создаём и запускаем экземпляр класса при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    new UserDashboard();
});