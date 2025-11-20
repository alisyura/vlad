
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

/**
 * Карта для транслитерации русских символов в латиницу (Упрощенная схема).
 */
const CYR_TO_LAT_MAP = {
    'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo', 'ж': 'zh',
    'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o',
    'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'kh', 'ц': 'ts',
    'ч': 'ch', 'ш': 'sh', 'щ': 'sch', 'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu',
    'я': 'ya'
};

/**
 * Фильтрует ввод, разрешая только латинские буквы (a-z, A-Z), цифры (0-9) и нижнее подчеркивание (_).
 * Перед фильтрацией производит транслитерацию русских букв в латиницу.
 * Вызывается через oninput="handleKeyInput(this)" в HTML.
 * @param {HTMLInputElement} input - Элемент input, в который производится ввод.
 */
function handleKeyInput(input) {
    let value = input.value;

    // 1. Приводим к нижнему регистру, чтобы соответствовать ключам в CYR_TO_LAT_MAP
    value = value.toLowerCase();

    // 2. Транслитерация русских символов в латиницу
    let transliteratedValue = '';
    for (let i = 0; i < value.length; i++) {
        const char = value[i];
        if (CYR_TO_LAT_MAP[char] !== undefined) {
            transliteratedValue += CYR_TO_LAT_MAP[char];
        } else {
            transliteratedValue += char;
        }
    }
    
    // 3. Удаляем все символы, не соответствующие латинским буквам, цифрам или нижнему подчеркиванию.
    const finalValue = transliteratedValue.replace(/[^a-z0-9_]/g, '');
    
    // 4. Обновляем значение поля ввода
    input.value = finalValue;
}