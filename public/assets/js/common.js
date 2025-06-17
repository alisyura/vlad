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

// document.addEventListener("DOMContentLoaded", function () {
//     const cookieBanner = document.getElementById('cookie-consent');
//     const acceptBtn = document.getElementById('accept-cookies');

//     // Проверяем, есть ли уже принятое согласие
//     if (!localStorage.getItem('cookiesAccepted')) {
//         cookieBanner.classList.add('show');
//     }

//     alert('11');
//     // При нажатии на кнопку "Согласиться"
//     acceptBtn.addEventListener('click', function () {
//         alert('22'+cookieBanner);
//         localStorage.setItem('cookiesAccepted', 'true');
//         cookieBanner.classList.remove('show');
//     });
// });

document.addEventListener("DOMContentLoaded", function () {
    const cookieBanner = document.getElementById('cookie-consent');
    const acceptBtn = document.getElementById('accept-cookies');

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