# Документация: Метод `joinWhere()`

## Назначение
Метод `joinWhere()` позволяет создавать сложные JOIN-запросы с поддержкой как условий `ON`, так и `WHERE` внутри самого соединения. Это обеспечивает более тонкий контроль над объединением таблиц и позволяет фильтровать данные на раннем этапе выполнения запроса.

---

## Синтаксис

```php
public function joinWhere(string $table, \Closure $callback, string $type = 'INNER'): self
```

### Параметры
`$table` `string` Обязательный | Имя таблицы для присоединения
`$callback` `\Closure` Обязательный | Функция, определяющая условия JOIN
`$type` `string` Не обязательный | по-умолчанию `'INNER'` | Тип JOIN: `INNER`, `LEFT`, `RIGHT`, `FULL`, `CROSS`

---

## Методы внутри замыкания

### `on(string $first, string $operator, string $second)`
Добавляет условие `ON` для связи таблиц.

**Параметры:**
- `$first` - левая часть условия (например, `'users.id'`)
- `$operator` - оператор сравнения (`=`, `!=`, `>`, `<`, `>=`, `<=`, `LIKE` и т.д.)
- `$second` - правая часть условия (например, `'orders.user_id'`)

**Пример:**
```php
$join->on('users.id', '=', 'orders.user_id')
```

### `where(mixed $column, mixed $operator = null, mixed $value = null)`
Добавляет условие `WHERE` внутри JOIN. Поддерживает несколько форматов вызова.

**Форматы вызова:**

1. **Простое условие:**
```php
$join->where('orders.status', 'active');
// или с оператором
$join->where('orders.amount', '>', 1000);
```

2. **С двумя аргументами** (предполагается оператор `=`):
```php
$join->where('orders.status', 'active');
// эквивалентно
$join->where('orders.status', '=', 'active');
```

3. **С вложенным замыканием** для группировки условий:
```php
$join->where(function($query) {
    $query->where('status', 'active')
          ->orWhere('status', 'pending');
});
```

---

## Примеры использования

### Пример 1: Базовый сложный JOIN

```php
$query->select('users.*', 'orders.total')
    ->from('users')
    ->joinWhere('orders', function($join) {
        // Основное условие соединения
        $join->on('users.id', '=', 'orders.user_id')
             // Дополнительные условия соединения (AND в ON)
             ->on('users.account_type', '=', 'orders.customer_type')
             // Фильтрация записей из таблицы orders ДО объединения
             ->where('orders.created_at', '>', '2024-01-01')
             ->where('orders.amount', '>', 100);
    }, 'LEFT')
    ->where('users.active', true);
```

**Сгенерированный SQL (примерно):**
```sql
SELECT users.*, orders.total 
FROM users 
LEFT JOIN orders ON users.id = orders.user_id 
                 AND users.account_type = orders.customer_type
WHERE orders.created_at > '2024-01-01' 
  AND orders.amount > 100
  AND users.active = true
```

---

### Пример 2: JOIN с группировкой условий (OR внутри JOIN)

```php
$query->joinWhere('order_items', function($join) {
    $join->on('orders.id', '=', 'order_items.order_id')
         // Сложная фильтрация с OR внутри JOIN
         ->where(function($subQuery) {
             $subQuery->where('order_items.status', 'shipped')
                      ->orWhere(function($innerQuery) {
                          $innerQuery->where('order_items.status', 'processing')
                                     ->where('order_items.updated_at', '>', '2024-06-01');
                      });
         })
         // Дополнительное условие
         ->where('order_items.quantity', '>', 0);
});
```

**Особенность:** Условия внутри вложенного замыкания группируются в скобки для правильной логики `OR`.

---

### Пример 3: Каскадные JOIN (несколько таблиц)

```php
$query->from('users')
    ->joinWhere('orders', function($join) {
        $join->on('users.id', '=', 'orders.user_id')
             ->where('orders.year', '=', 2024);
    })
    ->joinWhere('payments', function($join) {
        $join->on('orders.id', '=', 'payments.order_id')
             ->where('payments.status', 'completed');
    });
```

**Результат:** Получение пользователей с их заказами 2024 года и выполненными платежами.

---

### Пример 4: Разные типы JOIN

```php
// LEFT JOIN для получения всех пользователей, даже без заказов
$query->joinWhere('archived_orders', function($join) {
    $join->on('users.id', '=', 'archived_orders.user_id');
}, 'LEFT')

// RIGHT JOIN для получения всех удаленных записей
$query->joinWhere('deleted_users', function($join) {
    $join->on('users.id', '=', 'deleted_users.original_id');
}, 'RIGHT');
```

---

### Пример 5: Практический кейс — отчет по активным пользователям

```php
$monthlyReport = $query->select(
        'users.id',
        'users.name',
        'COUNT(orders.id) as order_count',
        'SUM(orders.total) as total_spent'
    )
    ->from('users')
    ->joinWhere('orders', function($join) {
        $join->on('users.id', '=', 'orders.user_id')
             // Исключаем отмененные заказы
             ->where('orders.status', '!=', 'cancelled')
             // Только заказы за 2024 год
             ->where('orders.created_at', '>=', '2024-01-01')
             ->where('orders.created_at', '<', '2025-01-01');
    })
    ->where('users.active', true)
    ->groupBy('users.id')
    ->orderBy('total_spent', 'DESC')
    ->get();
```

---

## Важные особенности

### 1. **Различие между ON и WHERE в JOIN**
- `ON` — определяет **как** таблицы связаны между собой
- `WHERE` внутри JOIN — фильтрует записи **присоединяемой таблицы** до объединения

### 2. **Когда использовать WHERE внутри JOIN**
- Когда нужно отфильтровать данные из второй таблицы **до** их объединения с первой
- Для оптимизации запросов (меньше данных для обработки)
- Для сложных бизнес-правил, которые должны применяться к соединению

### 3. **Цепочность вызовов**
Методы `on()` и `where()` возвращают `$this`, что позволяет строить цепочки:

```php
$join->on(...)->on(...)->where(...)->where(...)
```

### 4. **Производительность**
Использование `WHERE` внутри JOIN может улучшить производительность, так как:
- Фильтрация происходит на раннем этапе
- Уменьшается количество строк для объединения
- Особенно эффективно с большими таблицами

---

## Отличие от обычного JOIN

### Обычный подход:
```php
$query->join('orders', 'users.id', '=', 'orders.user_id')
      ->where('orders.status', 'active');
// Фильтрация ПОСЛЕ объединения таблиц
```

### С joinWhere():
```php
$query->joinWhere('orders', function($join) {
    $join->on('users.id', '=', 'orders.user_id')
         ->where('orders.status', 'active');
});
// Фильтрация ДО объединения таблиц
```

---

## Типичные сценарии использования

1. **Фильтрация исторических данных** — только актуальные записи
2. **Исключение невалидных записей** из соединения
3. **Сложные бизнес-правила** при объединении
4. **Оптимизация запросов** с большими таблицами
5. **Построение отчетов** со специфической логикой соединений

---

## Примечания по реализации

- Метод поддерживает все стандартные SQL-операторы
- Вложенные условия автоматически оборачиваются в скобки
- Значения параметризуются для защиты от SQL-инъекций
- Порядок условий сохраняется в порядке их добавления