
/**
 * Извлекает тип контента ('post' или 'page') из URL.
 * @param {string} url - Полный URL
 * @param {string} adminRoute - Префикс админ-маршрута, например 'adm'
 * @returns {string|null} Возвращает 'post' | 'page' или null.
 */
function getContentTypeFromUrlRegex(url, adminRoute) {
    // Регулярное выражение:
    // ^ - Начало строки
    // /adm/ - Ваш префикс
    // (posts|pages) - Группа захвата (match[1])
    // ([/?]|$) - Необязательно: после типа контента может идти слэш, знак вопроса, или конец строки.
    // const regex = new RegExp(`^/${adminRoute}/(posts|pages)([/?]|$)`);

    // Регулярное выражение:
    // ^ - Начало строки (обязательно)
    // /adm/ - Ваш префикс
    // (posts|pages) - Группа захвата (match[1])
    // ($|/|\\?) - После типа контента может быть: 
    //   $ - Конец строки (для /adm/posts)
    //   / - Слэш (для /adm/posts/create)
    //   \\? - Знак вопроса (если после типа идут параметры, хотя в pathname его не должно быть)
    const regex = new RegExp(`^/${adminRoute}/(posts|pages)($|/|\\?)`); 
    
    const match = url.match(regex);

    if (match && match[1]) {
        const contentTypePlural = match[1];
        return contentTypePlural === 'posts' ? 'post' : 'page';
    }

    return null;
}