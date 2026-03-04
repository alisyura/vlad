// Наш "интерфейс" (Базовый класс)
class BaseVideoProvider {
    // Метод для проверки, подходит ли ссылка этому провайдеру
    match(url) {
        throw new Error("Метод match() должен быть реализован");
    }

    // Метод, который возвращает готовый HTML для вставки
    getHtml(url) {
        throw new Error("Метод getHtml() должен быть реализован");
    }

    wrap(iframe) { 
        // Подгоняем видео под 16/9.
        // Если формат отличается, добавляются черные полосы
        const styledIframe = iframe.replace('<iframe', '<iframe style="width: 100%; height: auto; aspect-ratio: 16/9; object-fit: contain; background: black; border:0;"');
    
        return styledIframe;
    }
}

// Реализация для YouTube
class YoutubeProvider extends BaseVideoProvider {
    match(url) {
        return url.includes('youtube.com') || url.includes('youtu.be');
    }

    getHtml(url) {
        const videoId = this.extractId(url);
        if (!videoId) return '';
        return videoId 
            ? this.wrap(`<iframe src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen></iframe>`) 
            : null;
    }

    extractId(url) {
        const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        const match = url.match(regExp);
        return (match && match[2].length === 11) ? match[2] : null;
    }
}

// Реализация для Rutube
class RutubeProvider extends BaseVideoProvider {
    match(url) {
        return url.includes('rutube.ru');
    }

    getHtml(url) {
        const videoId = url.match(/video\/([a-z0-9]+)/i)?.[1];
        if (!videoId) return '';
        return videoId 
            ? this.wrap(`<iframe src="https://rutube.ru/play/embed/${videoId}" frameborder="0" allowfullscreen></iframe>`) 
            : null;
    }
}

// Реализация для Vimeo
class VimeoProvider extends BaseVideoProvider {
    match(url) {
        // Проверяем, что ссылка ведет на vimeo.com
        return url.includes('vimeo.com');
    }

    getHtml(url) {
        // Извлекаем ID видео из ссылки (обычно это цифры после слеша)
        const videoId = url.match(/vimeo\.com\/(\d+)/)?.[1];
        
        // Если ID найден, оборачиваем iframe в наш стандартный контейнер
        return videoId 
            ? this.wrap(`<iframe src="https://player.vimeo.com/video/${videoId}" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>`) 
            : null;
    }
}

class MailRuProvider extends BaseVideoProvider {
    match(url) {
        // Теперь проверяем не только URL, но и наличие iframe от mail.ru
        return url.includes('my.mail.ru');
    }

    getHtml(input) {
        let embedUrl = '';

        // 1. Если вставили целиком тег <iframe>, вытаскиваем из него src
        if (input.includes('<iframe')) {
            const srcMatch = input.match(/src=['"]([^'"]+)['"]/);
            embedUrl = srcMatch ? srcMatch[1] : '';
        } 
        // 2. Если вставили обычную ссылку со страницы
        else if (input.includes('.html')) {
            const videoPath = input.match(/video\/(.*)\.html/)?.[1];
            if (videoPath) {
                embedUrl = `https://my.mail.ru/video/embed/${videoPath}`;
            }
        } 
        // 3. Если вставили уже готовую прямую ссылку для встраивания
        else {
            embedUrl = input;
        }

        if (!embedUrl) return null;

        // Формируем код именно такого вида, как ты прислала
        return this.wrap(`
            <iframe 
                src="${embedUrl}" 
                frameborder="0" 
                scrolling="no" 
                webkitallowfullscreen 
                mozallowfullscreen 
                allowfullscreen>
            </iframe>
        `);
    }
}

class OkProvider extends BaseVideoProvider {
    match(url) {
        return url.includes('ok.ru/video/');
    }

    getHtml(url) {
        const videoId = url.match(/video\/(\d+)/)?.[1];
        return videoId 
            ? this.wrap(`<iframe src="https://ok.ru/videoembed/${videoId}" frameborder="0" allowfullscreen title="OK video player"></iframe>`) 
            : null;
    }
}

class VkProvider extends BaseVideoProvider {
    match(url) {
        return url.includes('vk.com/video') || url.includes('vkvideo.ru/video');
    }

    getHtml(url) {
        // Извлекаем oid (owner_id) и id видео
        // Пример: video-12345_67890
        const match = url.match(/video(-?\d+)_(\d+)/);
        if (match) {
            const oid = match[1];
            const vid = match[2];
            return this.wrap(`<iframe src="https://vkvideo.ru/video_ext.php?oid=${oid}&id=${vid}&hash=0" frameborder="0" allowfullscreen></iframe>`);
        }
        return null;
    }
}