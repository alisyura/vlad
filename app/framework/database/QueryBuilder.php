<?php
// app/Framework/Database/QueryBuilder.php

namespace App\Framework\Database;

use PDO;
use PDOStatement;

class QueryBuilder
{
    private $pdo;
    private $table;
    private $select = ['*'];
    private $where = [];
    private $bindings = [];
    private $orderBy = [];
    private $limit = null;
    private $offset = null;
    private $joins = [];
    private $groupBy = [];
    private $having = [];
    private $havingBindings = [];
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    // Устанавливаем таблицу
    public function table(string $table): self
    {
        $this->table = $table;
        $this->reset(); // Сбрасываем предыдущие условия
        return $this;
    }
    
    // Сброс условий для нового запроса
    private function reset(): void
    {
        $this->select = ['*'];
        $this->where = [];
        $this->bindings = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = null;
        $this->joins = [];
        $this->groupBy = [];
        $this->having = [];
        $this->havingBindings = [];
    }
    
    // SELECT
    public function select($columns = ['*']): self
    {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    public function selectRaw(string $expression, array $bindings = []): self
    {
        $this->select[] = $expression;
        $this->addBindings($bindings);
        return $this;
    }
    
    // WHERE
    public function where(string $column, string $operator, $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        
        return $this;
    }
    
    public function orWhere(string $column, string $operator, $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->where[] = [
            'type' => 'OR',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        
        return $this;
    }
    
    public function whereIn(string $column, array $values): self
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IN',
            'value' => '(' . $placeholders . ')'
        ];
        
        $this->addBindings($values);
        return $this;
    }
    
    public function whereNotIn(string $column, array $values): self
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'NOT IN',
            'value' => '(' . $placeholders . ')'
        ];
        
        $this->addBindings($values);
        return $this;
    }
    
    public function whereNull(string $column): self
    {
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IS',
            'value' => 'NULL'
        ];
        
        return $this;
    }
    
    public function whereNotNull(string $column): self
    {
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IS NOT',
            'value' => 'NULL'
        ];
        
        return $this;
    }
    
    public function whereBetween(string $column, $min, $max): self
    {
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'BETWEEN',
            'value' => ['min' => $min, 'max' => $max]
        ];
        
        $this->addBindings([$min, $max]);
        return $this;
    }
    
    // JOIN
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $type
        ];
        
        return $this;
    }
    
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }
    
    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Добавляет сложный JOIN с поддержкой условий ON и WHERE через замыкание
     * 
     * Позволяет создавать сложные условия соединения таблиц с возможностью
     * добавления дополнительных фильтров (WHERE) внутри самого JOIN.
     * 
     * @param string $table Имя таблицы для соединения
     * @param \Closure $callback Функция-замыкание, принимающая объект с методами
     *                         `on()` и `where()` для определения условий соединения
     * @param string $type Тип JOIN ('INNER', 'LEFT', 'RIGHT', 'FULL', 'CROSS')
     *                    По умолчанию 'INNER'
     * 
     * @return self Возвращает текущий экземпляр для цепочки вызовов
     * 
     * @example
     * // Простой JOIN с дополнительным условием
     * $query->joinWhere('orders', function($join) {
     *     $join->on('users.id', '=', 'orders.user_id')
     *          ->where('orders.status', 'active');
     * });
     * 
     * // Сложный JOIN с несколькими условиями
     * $query->joinWhere('orders', function($join) {
     *     $join->on('users.id', '=', 'orders.user_id')
     *          ->on('users.type', '=', 'orders.customer_type')
     *          ->where('orders.amount', '>', 1000)
     *          ->where('orders.created_at', '>=', '2024-01-01');
     * });
     * 
     * // JOIN с вложенными условиями
     * $query->joinWhere('orders', function($join) {
     *     $join->on('users.id', '=', 'orders.user_id')
     *          ->where(function($query) {
     *              $query->where('status', 'active')
     *                    ->orWhere('status', 'pending');
     *          });
     * });
     */
    public function joinWhere(string $table, \Closure $callback, string $type = 'INNER'): self
    {
        /**
         * Объект JoinBuilder, передаваемый в замыкание, предоставляет методы:
         * 
         * @method JoinBuilder on(string $first, string $operator, string $second)
         *                   Добавляет условие ON для JOIN
         *                   @param string $first Левая часть условия
         *                   @param string $operator Оператор (=, !=, >, < и т.д.)
         *                   @param string $second Правая часть условия
         * 
         * @method JoinBuilder where(mixed $column, mixed $operator = null, mixed $value = null)
         *                   Добавляет условие WHERE внутри JOIN
         *                   Поддерживает простые условия и вложенные замыкания
         */
        $join = new class {
            public $conditions = [];
            
            public function on($first, $operator, $second) {
                $this->conditions[] = [
                    'type' => 'ON',
                    'first' => $first,
                    'operator' => $operator,
                    'second' => $second
                ];
                return $this;
            }
            
            public function where($column, $operator, $value = null) {
                if (func_num_args() === 2) {
                    $value = $operator;
                    $operator = '=';
                }
                
                $this->conditions[] = [
                    'type' => 'WHERE',
                    'column' => $column,
                    'operator' => $operator,
                    'value' => $value
                ];
                return $this;
            }
        };
        
        $callback($join);
        
        $this->joins[] = [
            'table' => $table,
            'conditions' => $join->conditions,
            'type' => $type
        ];
        
        return $this;
    }
    
    // GROUP BY
    public function groupBy($columns): self
    {
        $this->groupBy = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    // HAVING
    public function having(string $column, string $operator, $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->having[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        
        $this->havingBindings[] = $value;
        return $this;
    }
    
    public function orHaving(string $column, string $operator, $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->having[] = [
            'type' => 'OR',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        
        $this->havingBindings[] = $value;
        return $this;
    }
    
    // ORDER BY
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }
    
    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'DESC');
    }
    
    // LIMIT/OFFSET
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }
    
    public function paginate(int $perPage, int $page = 1): array
    {
        $offset = ($page - 1) * $perPage;
        
        $this->limit($perPage);
        $this->offset($offset);
        
        $results = $this->get();
        $total = $this->cloneWithoutLimit()->count();
        
        return [
            'data' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }
    
    // МАССОВЫЕ ОПЕРАЦИИ
    public function insert(array $data): bool
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        return $this->execute($sql, array_values($data));
    }
    
    public function insertGetId(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        return (int) $this->pdo->lastInsertId();
    }
    
    public function insertBatch(array $data): int
    {
        if (empty($data)) {
            return 0;
        }
        
        $columns = implode(', ', array_keys($data[0]));
        $placeholders = '(' . implode(', ', array_fill(0, count($data[0]), '?')) . ')';
        
        $allValues = [];
        $rows = [];
        
        foreach ($data as $row) {
            $rows[] = $placeholders;
            $allValues = array_merge($allValues, array_values($row));
        }
        
        $rowsSql = implode(', ', $rows);
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES {$rowsSql}";
        
        return $this->execute($sql, $allValues) ? count($data) : 0;
    }
    
    public function update(array $data): int
    {
        $setParts = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts);
        
        list($whereSql, $whereValues) = $this->buildWhere();
        
        if ($whereSql) {
            $sql .= " WHERE {$whereSql}";
            $values = array_merge($values, $whereValues);
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        
        return $stmt->rowCount();
    }
    
    public function updateBatch(array $data, string $key = 'id'): int
    {
        if (empty($data)) {
            return 0;
        }
        
        $cases = [];
        $ids = [];
        $params = [];
        
        foreach ($data as $row) {
            $id = $row[$key];
            unset($row[$key]);
            
            foreach ($row as $field => $value) {
                if (!isset($cases[$field])) {
                    $cases[$field] = [];
                }
                $cases[$field][] = "WHEN ? THEN ?";
                $params[] = $id;
                $params[] = $value;
            }
            $ids[] = $id;
        }
        
        $sql = "UPDATE {$this->table} SET ";
        $updates = [];
        
        foreach ($cases as $field => $caseStatements) {
            $updates[] = "{$field} = CASE {$key} " . implode(' ', $caseStatements) . " END";
        }
        
        $sql .= implode(', ', $updates);
        $sql .= " WHERE {$key} IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
        
        $params = array_merge($params, $ids);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    public function upsert(array $data, array $uniqueBy, ?array $updateColumns = null): int
    {
        if (empty($data)) {
            return 0;
        }
        
        // Простая реализация для MySQL
        $columns = array_keys($data[0]);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        
        $allValues = [];
        $rows = [];
        
        foreach ($data as $row) {
            $rows[] = $placeholders;
            $allValues = array_merge($allValues, array_values($row));
        }
        
        $columnsSql = implode(', ', $columns);
        $rowsSql = implode(', ', $rows);
        
        if ($updateColumns === null) {
            $updateColumns = array_diff($columns, $uniqueBy);
        }
        
        $updateParts = [];
        foreach ($updateColumns as $column) {
            $updateParts[] = "{$column} = VALUES({$column})";
        }
        
        $sql = "INSERT INTO {$this->table} ({$columnsSql}) VALUES {$rowsSql} 
                ON DUPLICATE KEY UPDATE " . implode(', ', $updateParts);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($allValues);
        
        return $stmt->rowCount();
    }
    
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";
        list($whereSql, $whereValues) = $this->buildWhere();
        
        if ($whereSql) {
            $sql .= " WHERE {$whereSql}";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($whereValues);
        
        return $stmt->rowCount();
    }
    
    // GET
    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        list($whereSql, $whereValues) = $this->buildWhere();
        
        if ($whereSql) {
            $sql .= " WHERE {$whereSql}";
        }
        
        // GROUP BY
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }
        
        // HAVING
        list($havingSql, $havingValues) = $this->buildHaving();
        if ($havingSql) {
            $sql .= " HAVING {$havingSql}";
            $whereValues = array_merge($whereValues, $havingValues);
        }
        
        // ORDER BY
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }
        
        // LIMIT/OFFSET
        if ($this->limit !== null) {
            $sql .= " LIMIT " . $this->limit;
            
            if ($this->offset !== null) {
                $sql .= " OFFSET " . $this->offset;
            }
        }
        
        $allBindings = array_merge($this->bindings, $whereValues);
        
        return $this->fetchAll($sql, $allBindings);
    }
    
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        
        return $results[0] ?? null;
    }
    
    public function find($id, string $column = 'id'): ?array
    {
        return $this->where($column, $id)->first();
    }
    
    public function count(): int
    {
        $clone = $this->cloneWithoutSelectAndLimit();
        $clone->selectRaw('COUNT(*) as count');
        
        $result = $clone->first();
        
        return (int) ($result['count'] ?? 0);
    }
    
    public function exists(): bool
    {
        return $this->count() > 0;
    }
    
    public function max(string $column)
    {
        $clone = $this->cloneWithoutSelectAndLimit();
        $clone->selectRaw("MAX({$column}) as max_value");
        
        $result = $clone->first();
        
        return $result['max_value'] ?? null;
    }
    
    public function min(string $column)
    {
        $clone = $this->cloneWithoutSelectAndLimit();
        $clone->selectRaw("MIN({$column}) as min_value");
        
        $result = $clone->first();
        
        return $result['min_value'] ?? null;
    }
    
    public function sum(string $column)
    {
        $clone = $this->cloneWithoutSelectAndLimit();
        $clone->selectRaw("SUM({$column}) as sum_value");
        
        $result = $clone->first();
        
        return $result['sum_value'] ?? null;
    }
    
    public function avg(string $column)
    {
        $clone = $this->cloneWithoutSelectAndLimit();
        $clone->selectRaw("AVG({$column}) as avg_value");
        
        $result = $clone->first();
        
        return $result['avg_value'] ?? null;
    }
    
    // ПРИВАТНЫЕ МЕТОДЫ
    private function buildSelectQuery(): string
    {
        if (empty($this->table)) {
            throw new \Exception('Table not specified. Use ->table() method first.');
        }
        
        $select = implode(', ', $this->select);
        $sql = "SELECT {$select} FROM {$this->table}";
        
        // JOIN
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }
        
        return $sql;
    }
    
    private function buildWhere(): array
    {
        if (empty($this->where)) {
            return ['', []];
        }
        
        $whereClauses = [];
        $values = [];
        
        foreach ($this->where as $index => $condition) {
            $type = $index === 0 ? '' : $condition['type'];
            
            if (in_array($condition['operator'], ['IN', 'NOT IN'])) {
                $whereClauses[] = "{$type} {$condition['column']} {$condition['operator']} {$condition['value']}";
            } elseif (in_array($condition['operator'], ['IS', 'IS NOT'])) {
                $whereClauses[] = "{$type} {$condition['column']} {$condition['operator']} {$condition['value']}";
            } elseif ($condition['operator'] === 'BETWEEN') {
                $whereClauses[] = "{$type} {$condition['column']} BETWEEN ? AND ?";
                $values[] = $condition['value']['min'];
                $values[] = $condition['value']['max'];
            } else {
                $whereClauses[] = "{$type} {$condition['column']} {$condition['operator']} ?";
                $values[] = $condition['value'];
            }
        }
        
        $whereSql = implode(' ', $whereClauses);
        $whereSql = ltrim($whereSql, 'AND ');
        $whereSql = ltrim($whereSql, 'OR ');
        
        return [$whereSql, $values];
    }
    
    private function buildHaving(): array
    {
        if (empty($this->having)) {
            return ['', []];
        }
        
        $havingClauses = [];
        $values = [];
        
        foreach ($this->having as $index => $condition) {
            $type = $index === 0 ? '' : $condition['type'];
            $havingClauses[] = "{$type} {$condition['column']} {$condition['operator']} ?";
            $values[] = $condition['value'];
        }
        
        $havingSql = implode(' ', $havingClauses);
        $havingSql = ltrim($havingSql, 'AND ');
        $havingSql = ltrim($havingSql, 'OR ');
        
        return [$havingSql, $values];
    }
    
    private function execute(string $sql, array $bindings = []): bool
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($bindings);
    }
    
    private function fetchAll(string $sql, array $bindings = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function addBindings(array $bindings): void
    {
        $this->bindings = array_merge($this->bindings, $bindings);
    }
    
    private function cloneWithoutSelectAndLimit(): self
    {
        $clone = clone $this;
        $clone->select = ['*'];
        $clone->limit = null;
        $clone->offset = null;
        $clone->orderBy = [];
        return $clone;
    }
    
    private function cloneWithoutLimit(): self
    {
        $clone = clone $this;
        $clone->limit = null;
        $clone->offset = null;
        return $clone;
    }
    
    // Получить SQL для дебага
    public function toSql(): string
    {
        $sql = $this->buildSelectQuery();
        list($whereSql, ) = $this->buildWhere();
        
        if ($whereSql) {
            $sql .= " WHERE {$whereSql}";
        }
        
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }
        
        list($havingSql, ) = $this->buildHaving();
        if ($havingSql) {
            $sql .= " HAVING {$havingSql}";
        }
        
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }
        
        if ($this->limit !== null) {
            $sql .= " LIMIT " . $this->limit;
            
            if ($this->offset !== null) {
                $sql .= " OFFSET " . $this->offset;
            }
        }
        
        return $sql;
    }
}