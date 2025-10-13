
/**
 * Извлекает тип контента ('post' или 'page') из URL.
 * @param {string} adminRoute - Префикс админ-маршрута, например 'adm'
 * @returns {string|null} Возвращает 'post' | 'page' или null.
 */
function getContentTypeFromUrlRegex(adminRoute) {
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
    const url = window.location.pathname;
    const match = url.match(regex);

    if (match && match[1]) {
        const contentTypePlural = match[1];
        return contentTypePlural === 'posts' ? 'post' : 'page';
    }

    return null;
}

/**
 * Извлекает тип контента ('post' или 'page') из URL. Для работы с корзиной
 * @param {string} adminRoute - Префикс админ-маршрута, например 'adm'
 * @returns {string|null} Возвращает 'post' | 'page' или null.
 */
function getThrashContentTypeFromUrlRegex(adminRoute) {
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
    const url = window.location.pathname;
    const regex = new RegExp(`^/${adminRoute}/thrash/(posts|pages)($|/|\\?)`); 
    const match = url.match(regex);

    if (match && match[1]) {
        const contentTypePlural = match[1];
        return contentTypePlural === 'posts' ? 'post' : 'page';
    }

    return null;
}