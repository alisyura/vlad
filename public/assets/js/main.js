/**
 * Класс для управления меню "Поделиться" и его состоянием.
 */
class ShareManager {
    constructor() {
        this.overlay = null;
        this.setupEventListeners();
    }

    /**
     * Устанавливает слушатели событий для триггеров и для всего документа.
     */
    setupEventListeners() {
        document.querySelectorAll('.share-trigger').forEach(trigger => {
            trigger.addEventListener('click', this.handleShareTriggerClick.bind(this));
        });
        document.addEventListener('click', this.handleDocumentClick.bind(this));
    }

    /**
     * Обрабатывает клик по кнопке "Поделиться".
     * @param {Event} e 
     */
    handleShareTriggerClick(e) {
        e.preventDefault();
        e.stopPropagation();

        const dropdown = e.currentTarget.closest('.share-dropdown');
        const menu = dropdown.querySelector('.share-menu');
        const rect = e.currentTarget.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom;

        this.closeAllMenus();
        this.createOverlay();

        if (spaceBelow < 250) {
            menu.classList.add('up');
        } else {
            menu.classList.remove('up');
        }

        dropdown.classList.add('active');
    }

    /**
     * Закрывает меню при клике вне его области.
     * @param {Event} e 
     */
    handleDocumentClick(e) {
        if (!e.target.closest('.share-dropdown')) {
            this.closeAllMenus();
        }
    }

    /**
     * Создает затемнение фона.
     */
    createOverlay() {
        this.overlay = document.createElement('div');
        this.overlay.className = 'share-overlay';
        this.overlay.addEventListener('click', () => this.closeAllMenus());
        document.body.appendChild(this.overlay);
    }

    /**
     * Скрывает все открытые меню и удаляет оверлей.
     */
    closeAllMenus() {
        document.querySelectorAll('.share-dropdown.active').forEach(d => d.classList.remove('active'));
        document.querySelectorAll('.share-overlay').forEach(o => o.remove());
        this.overlay = null;
    }
}


/**
 * Класс для управления реакциями на посты (лайки/дизлайки) и загрузкой данных.
 */
class PostInteractionManager {
    constructor() {
        this.setupEventListeners();
        this.loadInitialVotes();
    }

    /**
     * Устанавливает слушатель событий для реакций.
     */
    setupEventListeners() {
        document.addEventListener('click', this.handleReactionClick.bind(this));
    }

    /**
     * Обрабатывает клик по кнопке лайка/дизлайка.
     * @param {Event} e 
     */
    async handleReactionClick(e) {
        const reactionLink = e.target.closest('.reaction');
        if (!reactionLink || !reactionLink.dataset.type) return;

        e.preventDefault();
        e.stopPropagation();

        const postPreview = reactionLink.closest('.post_preview, .post_full');
        const postId = postPreview.dataset.id;
        const type = reactionLink.dataset.type;

        if (reactionLink.classList.contains('disabled')) {
            showToast('Вы уже проголосовали за этот пост');
            return;
        }
        
        try {
            const csrfToken =  await getFreshCsrfToken();
            if (!csrfToken) {
                showToast('Не удалось получить токен, попробуйте снова.');
                return;
            }

            const data = {
                postUrl: postId,
                type: type
            };

            const response = await fetch('/api/reaction', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json', // Говорим серверу, что это JSON
                    'X-Requested-With': 'XMLHttpRequest', // Часто используется для определения AJAX-запроса
                    'X-CSRF-TOKEN': csrfToken // Добавляем токен
                },
                body: JSON.stringify(data),
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                showToast(result.message);
                return;
            }

            this.updatePostCounters(postPreview, result);
            this.replaceReactionButtons(postPreview, type);
            showToast('Спасибо за ваш голос!');

        } catch (err) {
            console.error('Ошибка:', err);
            showToast('Произошла ошибка при голосовании');
        }
    }

    /**
     * Обновляет счетчики лайков и дизлайков на посте.
     * @param {HTMLElement} postEl 
     * @param {Object} data 
     */
    updatePostCounters(postEl, data) {
        const likeCountEl = postEl.querySelector('.like_count');
        const dislikeCountEl = postEl.querySelector('.dislike_count');
        if (data.likes !== undefined && likeCountEl) {
            likeCountEl.textContent = data.likes;
        }
        if (data.dislikes !== undefined && dislikeCountEl) {
            dislikeCountEl.textContent = data.dislikes;
        }
    }

    /**
     * Заменяет кнопки реакций на картинки, если пользователь уже проголосовал.
     * @param {HTMLElement} postEl 
     * @param {string} votedType 
     */
    replaceReactionButtons(postEl, votedType) {
        const likeButton = postEl.querySelector('.reaction.like');
        const dislikeButton = postEl.querySelector('.reaction.dislike');
        this.replaceReactionLink(likeButton, votedType === 'like');
        this.replaceReactionLink(dislikeButton, votedType === 'dislike');
    }

    /**
     * Заменяет кнопку на статичную иконку.
     * @param {HTMLElement} button 
     * @param {boolean} isVoted 
     */
    replaceReactionLink(button, isVoted) {
        if (!button) return;
        const icon = button.querySelector('.reaction-icon');
        if (!icon) return;

        const parent = button.parentNode;
        const newImg = icon.cloneNode(true);
        if (isVoted) {
            newImg.src = this.addVotedToSrc(newImg.src);
        }
        parent.replaceChild(newImg, button);
    }

    /**
     * Добавляет суффикс "_voted" к имени файла иконки.
     * @param {string} originalPath 
     */
    addVotedToSrc(originalPath) {
        const suffix = '_voted';
        const lastDotIndex = originalPath.lastIndexOf('.');
        if (lastDotIndex === -1) {
            return originalPath + suffix;
        }
        const name = originalPath.slice(0, lastDotIndex);
        const ext = originalPath.slice(lastDotIndex);
        return name + suffix + ext;
    }

    /**
     * Загружает данные о голосах при загрузке страницы.
     */
    async loadInitialVotes() {
        const postElements = document.querySelectorAll('.post_preview, .post_full');
        if (postElements.length === 0) return;

        const postIds = Array.from(postElements).map(el => el.dataset.id);

        const requestBody = {
            posts: postIds
        };

        try {
            const response = await fetch('/api/get-post-votes', { 
                method: 'POST', 
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(requestBody) // Преобразуем объект в JSON-строку
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                console.error('Не удалось загрузить данные о голосах:', result.message);
                showToast('Не удалось загрузить данные о голосах');
                return;
            }

            result.votes.forEach(postData => {
                const postEl = document.querySelector(`.post_preview[data-id="${postData.post_url}"], .post_full[data-id="${postData.post_url}"]`);
                if (!postEl) return;

                this.updatePostCounters(postEl, {
                    likes: postData.likes_count,
                    dislikes: postData.dislikes_count
                });

                if (postData.user_vote) {
                    this.replaceReactionButtons(postEl, postData.user_vote);
                }
            });
        } catch (err) {
            console.error('Ошибка при загрузке голосов:', err);
            showToast('Произошла ошибка при загрузке голосов');
        }
    }
}


/**
 * Функция для копирования текста в буфер обмена.
 * @param {string} text 
 */
function copyTextToClipboard(text) {
    return new Promise((resolve, reject) => {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(resolve).catch(reject);
        } else {
            const textarea = document.createElement("textarea");
            textarea.value = text;
            textarea.style.position = "fixed";
            textarea.style.top = "-9999px";
            textarea.style.left = "-9999px";
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                resolve();
            } catch (err) {
                reject(err);
            } finally {
                document.body.removeChild(textarea);
            }
        }
    });
}

// Глобальные обработчики для кнопок "Поделиться" и "Копировать"
window.copyLink = async function (e) {
    e.preventDefault();
    e.stopPropagation();
    const dropdown = e.target.closest('.share-dropdown');
    const postPreview = dropdown?.closest('.post_preview, .post_full');
    if (!postPreview || !postPreview.dataset.url) {
        showToast('Не удалось найти ссылку');
        return;
    }
    try {
        await copyTextToClipboard(postPreview.dataset.url);
        showToast('Ссылка скопирована!');
        dropdown.classList.remove('active');
        document.querySelector('.share-overlay')?.remove();
    } catch (err) {
        console.error('Ошибка копирования:', err);
        showToast('Не удалось скопировать ссылку');
    }
};

window.shareTo = function (platform, e) {
    e.preventDefault();
    e.stopPropagation();
    const dropdown = e.target.closest('.share-dropdown');
    const postPreview = dropdown?.closest('.post_preview, .post_full');
    if (!postPreview || !postPreview.dataset.url) {
        showToast('Не удалось найти ссылку');
        return;
    }
    let shareUrl = '';
    const postUrl = encodeURIComponent(postPreview.dataset.url);
    switch (platform) {
        case 'vk':
            shareUrl = `https://vk.com/share.php?url=${postUrl}`;
            break;
        case 'tg':
            shareUrl = `tg://msg_url?url=${postUrl}`;
            break;
        case 'wa':
            shareUrl = `whatsapp://send?text=${postUrl}`;
            break;
        case 'ok':
            shareUrl = `https://connect.ok.ru/offer?url=${postUrl}`;
            break;
        default:
            return;
    }
    window.open(shareUrl, '_blank');
};


// Инициализация классов при загрузке документа
document.addEventListener("DOMContentLoaded", function () {
    new ShareManager();
    new PostInteractionManager();
});

