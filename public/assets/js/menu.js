document.addEventListener('DOMContentLoaded', function () {
    // Получаем элементы из DOM
    const burgerMenu = document.querySelector('.menu-toggle');
    const menuOverlay = document.querySelector('.mobilemenu-overlay');
    const body = document.body;

    // Функция для закрытия мобильного меню
    function closeMobileMenu() {
        burgerMenu.classList.remove('active'); // Возвращаем состояние кнопок
        menuOverlay.classList.remove('visible'); // Скрываем фоновую плашку
        menuOverlay.classList.add('hidden');
        body.classList.remove('no-scroll'); // Включаем прокрутку
    }

    // Открытие меню при нажатии на кнопку "бургер"
    burgerMenu.addEventListener('click', function () {
        if (!burgerMenu.classList.contains('active')) {
            burgerMenu.classList.add('active'); // Переключаем состояние кнопок
            menuOverlay.classList.remove('hidden'); // Показываем фоновую плашку
            menuOverlay.classList.add('visible');
            body.classList.add('no-scroll'); // Отключаем прокрутку
        } else {
            closeMobileMenu();
        }
    });

    // Закрытие меню при изменении размера окна
    window.addEventListener('resize', function () {
        if (window.innerWidth > 1024) { // Если ширина больше 1024px
            closeMobileMenu(); // Автоматически закрываем мобильное меню
        }
    });
});