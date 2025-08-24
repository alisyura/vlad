Отлично! Вот подробное содержание для `DEBUGGING.md`:

# Debugging Guide

## 🐛 Отладка PHP с Xdebug в VS Code

## 📋 Оглавление
- [Быстрый старт](#-быстрый-старт)
- [Настройка окружения](#-настройка-окружения)
- [Способы запуска отладки](#-способы-запуска-отладки)
- [Конфигурация launch.json](#-конфигурация-launchjson)
- [Частые проблемы](#-частые-проблемы)
- [Полезные советы](#-полезные-советы)

## 🚀 Быстрый старт

### Минимальные шаги для запуска:
1. **Запустите отладчик в VS Code:**
   - Откройте панель Debug (Ctrl+Shift+D / Cmd+Shift+D)
   - Выберите конфигурацию "Xdebug"
   - Нажмите F5 или "Start Debugging"

2. **Активируйте отладку в браузере:**
   ```
   http://ваш-сайт/test.php?XDEBUG_SESSION_START=VSCODE
   ```

3. **Установите точки останова в коде** (клик на левом поле у номеров строк)

## ⚙️ Настройка окружения

### Требования:
- PHP с установленным Xdebug
- VS Code с расширением PHP Debug

### Проверка Xdebug:
```bash
php -v | grep Xdebug
# Должно показать: with Xdebug v3.x.x

# Или:
php --ri xdebug
```

### Настройка php.ini:
```ini
[xdebug]
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_port=9003
xdebug.idekey=VSCODE
xdebug.log=/tmp/xdebug.log
```

## 🎯 Способы запуска отладки

### 1. Через URL параметр (самый простой)
```
http://localhost/project/index.php?XDEBUG_SESSION_START=VSCODE
http://localhost/project/index.php?XDEBUG_SESSION=VSCODE
```

### 2. Через cookie (постоянная отладка)
Установите cookie в браузере:
```javascript
document.cookie = "XDEBUG_SESSION=VSCODE; path=/";
```

### 3. Через расширение браузера
Установите одно из расширений:
- **Xdebug Helper** (Firefox/Chrome)
- **Xdebug-launcher** (Chrome)

Настройте IDE key: **VSCODE**

### 4. Через POST/CLI запросы
```bash
# Для CLI:
XDEBUG_SESSION=VSCODE php script.php

# Для curl:
curl -H "Cookie: XDEBUG_SESSION=VSCODE" http://localhost
```

## 🔧 Конфигурация launch.json

Текущая конфигурация (файл: `.vscode/launch.json`):
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "C:/Users/kriya/Projects/web/vlad.local": "${workspaceFolder}"
            },
            "log": false
        },
        {
            "name": "Launch currently open script",
            "type": "php",
            "request": "launch",
            "program": "${file}",
            "cwd": "${fileDirname}",
            "port": 9003
        }
    ]
}
```

### Настройка pathMappings:
Укажите соответствие путей между сервером и локальной машиной:
```json
"pathMappings": {
    "/remote/path/to/project": "${workspaceFolder}",
    "/var/www/html": "${workspaceFolder}",
    "/app": "${workspaceFolder}"
}
```

## 🔍 Частые проблемы

### ❌ "Waiting for incoming connection..."
**Решение:**
- Проверьте, что Xdebug запущен на порту 9003
- Убедитесь, что добавлен параметр к URL
- Проверьте firewall правила

### ❌ Точки останова не срабатывают
**Решение:**
- Проверьте pathMappings в launch.json
- Убедитесь, что файлы на сервере и локально идентичны

### ❌ Xdebug не подключается
**Решение:**
1. Проверьте логи Xdebug:
```bash
tail -f /tmp/xdebug.log
```

2. Проверьте порт:
```bash
netstat -tulpn | grep 9003
```

## 💡 Полезные советы

### Горячие клавиши VS Code:
- `F5` - Start/Continue
- `F9` - Toggle breakpoint
- `F10` - Step over
- `F11` - Step into
- `Shift+F11` - Step out
- `Ctrl+Shift+F5` - Restart debug session

### Переменные окружения для разработки:
```bash
export XDEBUG_CONFIG="idekey=VSCODE"
export PHP_IDE_CONFIG="serverName=localhost"
```

### Docker-специфичные настройки:
Если используете Docker, добавьте в `docker-compose.yml`:
```yaml
environment:
  - XDEBUG_CONFIG=client_host=host.docker.internal idekey=VSCODE
  - PHP_IDE_CONFIG=serverName=Docker
```

## 📞 Поддержка

Если возникли проблемы:
1. Проверьте логи Xdebug: `/tmp/xdebug.log`
2. Убедитесь, что порт 9003 доступен
3. Проверьте настройки php.ini
4. Сверьте версию Xdebug (должна быть 3.x)

## 🔗 Полезные ссылки

- [Официальная документация Xdebug](https://xdebug.org/docs/)
- [Расширение PHP Debug для VS Code](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug)
- [Настройка Xdebug с Docker](https://gist.github.com/grzegorzk/4d01b4920ba4c4ed5b27f7430f3a1h2c)

---

**Примечание:** Этот файл автоматически обновляется при изменении конфигурации отладки. Все разработчики проекта должны ознакомиться с данным руководством.