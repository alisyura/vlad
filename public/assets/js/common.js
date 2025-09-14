// === 6. Всплывающее уведомление ===
function showToast(message) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');

    if (!toast || !toastMessage) return;

    toastMessage.textContent = message;
    toast.classList.remove('hidden');
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
        toast.classList.add('hidden');
    }, 3000);
}

// === Валидация email ===
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}

document.addEventListener("DOMContentLoaded", function () {
    const cookieBanner = document.getElementById('cookie-consent');
    const acceptBtn = document.getElementById('accept-cookies');

    // Проверяем, существуют ли оба элемента. Если хотя бы одного нет, выходим из функции.
    if (!cookieBanner || !acceptBtn) {
        return; 
    }
    
    // Проверяем, есть ли согласие
    if (!localStorage.getItem('cookiesAccepted')) {
        cookieBanner.classList.add('show');
    }

    // При нажатии на "Согласиться"
    acceptBtn.addEventListener('click', function () {
        localStorage.setItem('cookiesAccepted', 'true');

        // Скрываем баннер
        cookieBanner.classList.remove('show');

        // Через 300 мс полностью убираем его из DOM (по желанию)
        setTimeout(() => {
            cookieBanner.style.display = 'none';
        }, 300);
    });
});

function getAttribs(obj)
{
    if (obj == null) return;
    
    let attributes = '';
    for (const attr of obj.attributes) {
        attributes += `${attr.name}="${attr.value}"\n`;
    }
    console.log(attributes);
}

function getFormData(obj)
{
    for (let [key, value] of obj) {
        console.log(key, value);
    }
}

/**
 * Получает свежий CSRF-токен с сервера.
 * @returns {Promise<string|null>}
 */
window.getFreshCsrfToken = async function() {
    try {
        const response = await fetch('/api/get-csrf-token', { cache: 'no-store' });
        const data = await response.json();
        return data.csrf_token;
    } catch (error) {
        console.error('Ошибка при получении CSRF-токена:', error);
        return null;
    }
}