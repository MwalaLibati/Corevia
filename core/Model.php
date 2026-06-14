<?php

declare(strict_types=1);

/**
 * Base model for shared PDO CRUD operations.
 */

abstract class Model
{
    protected PDO $db;
    protected string $table;

    /**
     * Set to true in subclasses whose tables have a company_id column.
     * When true, find() / findAll() / delete() are automatically scoped
     * to the active tenant so cross-tenant data leakage is impossible.
     */
    protected bool $tenantScoped = false;

    public function __construct()
    {
        $this->db = db();
    }

    public function find(int $id): ?array
    {
        if ($this->tenantScoped && Tenant::id() > 0) {
            $stmt = $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE id = :id AND company_id = :cid LIMIT 1"
            );
            $stmt->execute(['id' => $id, 'cid' => Tenant::id()]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
        }

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAll(array $conditions = []): array
    {
        $sql = "SELECT * FROM {$this->table}";

        if ($this->tenantScoped && Tenant::id() > 0) {
            $conditions['company_id'] = Tenant::id();
        }

        if ($conditions !== []) {
            $parts = [];
            foreach ($conditions as $column => $_value) {
                $parts[] = $column . ' = :' . $column;
            }
            $sql .= ' WHERE ' . implode(' AND ', $parts);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($conditions);

        return $stmt->fetchAll();
    }

    public function insert(array $data): int
    {
        if ($this->tenantScoped && Tenant::id() > 0 && !isset($data['company_id'])) {
            $data['company_id'] = Tenant::id();
        }

        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $parts = [];
        foreach ($data as $column => $_value) {
            $parts[] = $column . ' = :' . $column;
        }

        $sql = sprintf('UPDATE %s SET %s WHERE id = :id', $this->table, implode(', ', $parts));

        if ($this->tenantScoped && Tenant::id() > 0) {
            $sql .= ' AND company_id = :cid';
            $data['cid'] = Tenant::id();
        }

        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($data);
    }

    public function delete(int $id): bool
    {
        if ($this->tenantScoped && Tenant::id() > 0) {
            $stmt = $this->db->prepare(
                "DELETE FROM {$this->table} WHERE id = :id AND company_id = :cid"
            );
            return $stmt->execute(['id' => $id, 'cid' => Tenant::id()]);
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
