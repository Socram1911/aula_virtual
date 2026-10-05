<?php

declare(strict_types=1);

namespace App\Core\Database;

/**
 * Constructor de consultas sencillo con parámetros vinculados.
 * Evita la concatenación de entrada del usuario en SQL.
 */
final class QueryBuilder
{
    private string $table;
    private string $type = 'select';
    private array $columns = ['*'];
    private array $wheres = [];
    private array $params = [];
    private string $orderBy = '';
    private string $limit = '';
    private array $insertData = [];
    private array $updateData = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public static function table(string $table): self
    {
        return new self($table);
    }

    public function select(array $columns = ['*']): self
    {
        $this->type = 'select';
        $this->columns = $columns;
        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $placeholder = 'p' . count($this->params);
        $this->wheres[] = "{$column} {$operator} :{$placeholder}";
        $this->params[$placeholder] = $value;
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = "{$column} IS NULL";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy = " ORDER BY {$column} {$direction}";
        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = " LIMIT {$limit} OFFSET {$offset}";
        return $this;
    }

    public function get(): array
    {
        $sql = $this->toSelectSql();
        return Connection::fetchAll($sql, $this->params);
    }

    public function first(): ?array
    {
        $rows = $this->limit(1)->get();
        return $rows[0] ?? null;
    }

    public function count(): int
    {
        $where = $this->wheres ? ' WHERE ' . implode(' AND ', $this->wheres) : '';
        $sql = "SELECT COUNT(*) FROM {$this->table}{$where}";
        return (int) Connection::fetch($sql, $this->params)['COUNT(*)'] ?? (int) Connection::fetch($sql, $this->params)['count'] ?? 0;
    }

    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn ($c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        return Connection::insert($sql, $data);
    }

    public function update(array $data): bool
    {
        $sets = [];
        $params = $this->params;
        foreach ($data as $column => $value) {
            $placeholder = 'u' . count($params);
            $sets[] = "{$column} = :{$placeholder}";
            $params[$placeholder] = $value;
        }
        $where = $this->wheres ? ' WHERE ' . implode(' AND ', $this->wheres) : '';
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . $where;
        Connection::run($sql, $params);
        return true;
    }

    public function delete(): bool
    {
        $where = $this->wheres ? ' WHERE ' . implode(' AND ', $this->wheres) : '';
        $sql = "DELETE FROM {$this->table}{$where}";
        Connection::run($sql, $this->params);
        return true;
    }

    private function toSelectSql(): string
    {
        $where = $this->wheres ? ' WHERE ' . implode(' AND ', $this->wheres) : '';
        return sprintf(
            'SELECT %s FROM %s%s%s%s',
            implode(', ', $this->columns),
            $this->table,
            $where,
            $this->orderBy,
            $this->limit
        );
    }
}
