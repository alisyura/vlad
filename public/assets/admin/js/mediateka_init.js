document.addEventListener('DOMContentLoaded', () => {
    // Инициализируем для страницы админки

    // Существует ли переменная adminRoute вообще?
    // adminRoute определена в шаблоне admin_layout.php
    const route = (typeof adminRoute !== 'undefined') ? `${adminRoute}` : '';

    if (!route) {
        console.error('Ошибка: Переменная adminRoute не определена в шаблоне!');
        // Можно вывести сообщение пользователю, если это критично
        return; 
    }

    const adminMedia = new MediaLibrary({ 
        adminRoute: `${adminRoute}`,
        isAdminMode: true 
    });
});