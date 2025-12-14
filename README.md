# Проект: [Название проекта]

## 🚀 Краткое описание

Легковестный php framework. 
Реализует принципы: 
- Dependency Injection (Service Container)
- Middleware-слой
- Объектная модель Request/Response
- Сервисный слой (Service Layer)
- Принципы SOLID

Всё — в рамках одной, цельной системы.

## 📦 Быстрый старт

### Требования
- PHP 8.0+
- Веб-сервер Apache
- База данных MySQL

## 🛠 Технологии

- **Backend:** Чистый PHP 8.x, самописный MVC фреймворк
- **Frontend:** Vanilla JavaScript (чистый JavaScript)
- **База данных:** [MySQL]
- **Сервер:** Apache с mod_rewrite
- **Отладка:** Xdebug 3.x, VS Code

## 📁 Структура проекта

```
project/
├── .vscode/                # Конфигурация VS Code
│   └── launch.json         # Настройки отладки
├── app/                    # Ядро приложения
│   ├── core/               # Настройки/вспомогательные файлы
│   ├── exceptions/         # Исключения
│   ├── framework/          # Сам фреймворк
│   ├── handlers/           # Обработчики
│   ├── http/               # Обработчики HTTP запросов
│   │   ├── controllers/    # Контроллеры
│   │   ├── factories/      # Фабрики
│   │   └── response/       # Кастомизированные объекты Response
│   ├── middleware/         # Обработчики Middleware
│   ├── models/             # Модели
│   ├── services/           # Service Layer
│   ├── traits/             # Для трейтов
│   ├── validators/         # Классы валидаторов
│   └── views/              # Файлы шаблонов вывода html страниц
├── public/                 # Public assets
│   ├── assets/             # Файлы для html страниц
│   │   ├── admin/          # Файлы для админки
│   │   ├── js/             # JavaScript файлы (Vanilla JS)
│   │   ├── css/            # Стили
│   │   ├── pic/            # Изображения
│   │   └── tinymce/        # Файлы WYSIWYG редактора TinyMCE
│   └── index.php           # Точка входа
├── sql/                    # Дамп БД
├── vendor/                 # Файлы автозагрузки классов, сторонние библиотеки
├── .env                    # Файл с настройками подключений к внешним серверам
└── README.md               📖 Этот файл
```

## ⚙️ Конфигурация

### Настройка config файлов
Отредактируйте файлы в корневой папке согласно вашим настройкам:

**.ENV**
```php
# Настройки базы данных
DB_HOST=
DB_NAME=
DB_USER=
DB_PASS=

# Настройки почты (PHPMailer)
# Исползовать уникальные имена переменных, чтобы не конфликтовать со средой
MAIL_HOST=
MAIL_FROM_USERNAME=
MAIL_ADMIN_USERNAME=
MAIL_PASSWORD=
MAIL_PORT=465

# Режим приложения: true для разработки, false для продакшена
APP_DEBUG=true

# ----------------------------------------
# Секретный ключ приложения (32 символа)
# ----------------------------------------
APP_SECRET_KEY=
```

### Настройка VS Code
Проект уже включает предварительно настроенные файлы:
- `.vscode/launch.json` - конфигурация отладчика Xdebug

## 📊 База данных

Если используется база данных:

```bash
# Импорт дампа (пример)
mysql -u username -p database_name < dump.sql
```

## 🚀 Production deployment

```bash
# Переключение в production режим
# Отредактируйте app/core/Config.php:
public static function isDev()
{
    return false;
}

# Убедитесь, что права на папки правильные
chmod -R 755 public/assets/uploads
chmod -R 755 logs/
chmod -R 755 cache/
```

## 🤝 Разработка

### Code style
Рекомендуется придерживаться PSR-12 стандарта:

```bash
# Ручная проверка стиля кода
# Можно использовать PHP_CodeSniffer вручную
```

### Git workflow
1. Создайте feature branch: `git checkout -b feature/name`
2. Сделайте изменения
3. Протестируйте с отладкой при необходимости
4. Создайте pull request

## ❗ Частые проблемы

### Проблемы с отладкой?
1. Проверьте, что Xdebug установлен: `php -v | grep Xdebug`
2. Убедитесь, что порт 9003 свободен

### Проблемы с веб-сервером?
- Убедитесь, что mod_rewrite включен (для Apache)
- Проверьте настройки виртуального хоста
- Убедитесь, что точка входа - `public/index.php`

### Проблемы с маршрутизацией?
- Проверьте настройки `.htaccess` в папке `public/`
- Убедитесь, что сервер разрешает использование `.htaccess`

---

**Примечание:** Для эффективной разработки рекомендуется использовать VS Code с расширением PHP Debug. Проект использует чистый PHP без внешних зависимостей, кроме PHPMailer.