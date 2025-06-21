document.addEventListener("DOMContentLoaded", async function () {
    // === 1. Открытие меню "Поделиться" ===
    document.querySelectorAll('.share-trigger').forEach(trigger => {
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const dropdown = this.closest('.share-dropdown');
            const menu = dropdown.querySelector('.share-menu');
            const rect = this.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;

            // Скрываем все открытые меню
            document.querySelectorAll('.share-dropdown.active').forEach(d => d.classList.remove('active'));
            document.querySelectorAll('.share-overlay').forEach(o => o.remove());

            // Создаем затемнение фона
            const overlay = document.createElement('div');
            overlay.className = 'share-overlay';
            document.body.appendChild(overlay);

            // Определяем направление открытия
            if (spaceBelow < 250) {
                menu.classList.add('up');
            } else {
                menu.classList.remove('up');
            }

            // Показываем текущее меню
            dropdown.classList.add('active');
        });
    });

    // === 2. Закрытие при клике вне области ===
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.share-dropdown')) {
            document.querySelectorAll('.share-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });

            document.querySelectorAll('.share-overlay').forEach(o => o.remove());
        }
    });

    // === 3. Копирование ссылки в буфер обмена ===
    window.copyLink = async function (e) {
        e.preventDefault();
        e.stopPropagation();

        const dropdown = e.target.closest('.share-dropdown');
        const postPreview = dropdown?.closest('.post_preview, .post_full');

        if (!postPreview) {
            showToast('Не удалось найти ссылку');
            return;
        }

        const postUrl = postPreview.dataset.url;

        try {
            await navigator.clipboard.writeText(postUrl);
            showToast('Ссылка скопирована!');
            dropdown.closest('.share-dropdown').classList.remove('active');
            document.querySelector('.share-overlay')?.remove();
        } catch (err) {
            console.error('Ошибка копирования:', err);
            showToast('Не удалось скопировать ссылку');
        }
    };

    // === 4. Поделиться в соцсетях ===
    window.shareTo = function (platform, e) {
        e.preventDefault();
        e.stopPropagation();

        const dropdown = e.target.closest('.share-dropdown');
        const postPreview = dropdown?.closest('.post_preview, .post_full');

        if (!postPreview) {
            showToast('Не удалось найти ссылку');
            return;
        }

        const postUrl = postPreview.dataset.url;
        let shareUrl = '';

        switch (platform) {
            case 'vk':
                shareUrl = `https://vk.com/share.php?url=${encodeURIComponent(postUrl)}`;
                break;
            case 'tg':
                shareUrl = `tg://msg_url?url=${encodeURIComponent(postUrl)}`;
                break;
            case 'wa':
                shareUrl = `whatsapp://send?text=${encodeURIComponent(postUrl)}`;
                break;
            case 'ok':
                shareUrl = `https://connect.ok.ru/offer?url=${encodeURIComponent(postUrl)}`;
                break;
            default:
                return;
        }

        window.open(shareUrl, '_blank');
    };

    // === 5. Лайк / дизлайк с AJAX запросом ===
    document.addEventListener('click', async function (e) {
        const reactionLink = e.target.closest('.reaction');

        if (!reactionLink || !reactionLink.dataset.type) return;

        e.preventDefault();
        e.stopPropagation();

        const postPreview = reactionLink.closest('.post_preview, .post_full');
        const postUrl = postPreview.dataset.id;
        const type = reactionLink.dataset.type;

        // Защита: если уже голосовали — не отправляем повторно
        if (reactionLink.classList.contains('disabled')) {
            showToast('Вы уже проголосовали за этот пост');
            return;
        }

        // === Отправка данных на сервер ===
        const data = new FormData();
        data.append('postUrl', postUrl);
        data.append('type', type);

        try {
            const response = await fetch('/api/reaction', {
                method: 'POST',
                body: data,
            });

            const result = await response.json();

            console.error('Ответ сервера:\n'+JSON.stringify(result, null, 2));
            
            if (!response.ok || !result.success) {
                showToast(result.message);
                return;
            }

            // Для теста используем заглушку
            // const result = { likes: 5, dislikes: 767 };

            const likeCountEl = postPreview.querySelector('.like_count');
            const dislikeCountEl = postPreview.querySelector('.dislike_count');

            if (result.likes !== undefined && likeCountEl) {
                likeCountEl.textContent = result.likes;
            }

            if (result.dislikes !== undefined && dislikeCountEl) {
                dislikeCountEl.textContent = result.dislikes;
            }

            // === УДАЛЯЕМ ССЫЛКИ И ОСТАВЛЯЕМ ТОЛЬКО IMG У ОБЕИХ РЕАКЦИЙ ===
            const likeButton = postPreview.querySelector('.reaction.like');
            const dislikeButton = postPreview.querySelector('.reaction.dislike');

            replaceReactionLink(likeButton, type == 'like');
            replaceReactionLink(dislikeButton, type == 'dislike');

            showToast('Спасибо за ваш голос!');
        } catch (err) {
            console.error('Ошибка:', err);
            showToast('Произошла ошибка при голосовании');
        }
    });




    // === 6. Загрузка данных о голосах при открытии страницы ===
    const postElements = document.querySelectorAll('.post_preview, .post_full');
    if (postElements.length > 0) {
        const postIds = Array.from(postElements).map(el => el.dataset.id);

        // === Отправка данных на сервер ===
        const data = new FormData();
        postIds.forEach(id => data.append('posts[]', id));

        try {
            const response = await fetch('/api/post-votes', {
                method: 'POST',
                body: data
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                showToast('Не удалось загрузить данные о голосах:', result.message);
                return;
            }

            // Обновляем интерфейс
            result.votes.forEach(postData => {
                const postEl = document.querySelector(
                    `.post_preview[data-id="${postData.post_url}"], 
                     .post_full[data-id="${postData.post_url}"]`
                );
                if (!postEl) return;

                const likeBtn = postEl.querySelector('.reaction.like');
                const dislikeBtn = postEl.querySelector('.reaction.dislike');
                const likeCount = postEl.querySelector('.like_count');
                const dislikeCount = postEl.querySelector('.dislike_count');

                // Обновляем счётчики
                if (likeCount) likeCount.textContent = postData.likes_count;
                if (dislikeCount) dislikeCount.textContent = postData.dislikes_count;

                // Блокируем кнопки, если пользователь уже голосовал
                if ((postData.user_vote === 'like' && likeBtn) ||
                    (postData.user_vote === 'dislike' && dislikeBtn))
                {
                    replaceReactionLink(likeBtn, postData.user_vote === 'like' && likeBtn);
                    replaceReactionLink(dislikeBtn, postData.user_vote === 'dislike' && dislikeBtn);
                }
            });
        } catch (err) {
            console.error('Ошибка при загрузке голосов:', err);
        }
    }
});

function addVotedToSrc(originalPath)
{
    // Разбиваем строку по точке
    const suffix = '_voted';
    const lastDotIndex = originalPath.lastIndexOf('.');
    if (lastDotIndex === -1) {
        return originalPath + suffix;
    }
    const name = originalPath.slice(0, lastDotIndex);
    const ext = originalPath.slice(lastDotIndex);
    const newPath = name + suffix + ext;

    return newPath;
}

function replaceReactionLink(button, change_img) {
    if (!button) return;

    const icon = button.querySelector('.reaction-icon');
    if (!icon) return;

    const parent = button.parentNode;
    const newImg = icon.cloneNode(true);

    if (change_img)
    {
        newImg.src = addVotedToSrc(newImg.src);
    }

    parent.replaceChild(newImg, button);
}