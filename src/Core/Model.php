<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

abstract class Model
{
    protected PDO $db;
    protected string $table; // To be defined in child models

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Find a record by its ID.
     *
     * @param int $id
     * @return array|false Associative array of the record, or false if not found.
     */
    public function find(int $id): array|false
    {
        if (empty($this->table)) {
            throw new \LogicException(get_class($this) . " must have a \$table property defined.");
        }
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all records from the table.
     *
     * @param string|null $orderBy Column to order by (e.g., 'created_at DESC')
     * @return array
     */
    public function findAll(string $orderBy = null): array
    {
        if (empty($this->table)) {
            throw new \LogicException(get_class($this) . " must have a \$table property defined.");
        }
        $sql = "SELECT * FROM {$this->table}";
        if ($orderBy) {
            // Basic sanitization for order by to prevent SQL injection.
            // More robust solution might involve whitelisting columns.
            $orderBy = preg_replace('/[^a-zA-Z0-9_ ,DESCASC]/', '', $orderBy);
            $sql .= " ORDER BY " . $orderBy;
        }
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Creates a new record.
     *
     * @param array $data Associative array of column => value.
     * @return string|false The ID of the newly inserted row, or false on failure.
     */
    public function create(array $data): string|false
    {
        if (empty($this->table)) {
            throw new \LogicException(get_class($this) . " must have a \$table property defined.");
        }
        if (empty($data)) {
            return false;
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->db->prepare($sql);

        try {
            $this->db->beginTransaction();
            $stmt->execute($data);
            $lastId = $this->db->lastInsertId();
            $this->db->commit();
            return $lastId;
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // Log error: error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Updates an existing record by ID.
     *
     * @param int $id The ID of the record to update.
     * @param array $data Associative array of column => value.
     * @return bool True on success, false on failure.
     */
    public function update(int $id, array $data): bool
    {
        if (empty($this->table)) {
            throw new \LogicException(get_class($this) . " must have a \$table property defined.");
        }
        if (empty($data)) {
            return false;
        }

        $setClauses = [];
        foreach (array_keys($data) as $key) {
            $setClauses[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setClauses);
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $data['id'] = $id; // Add id to data for binding

        try {
            return $stmt->execute($data);
        } catch (\PDOException $e) {
            // Log error: error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a record by its ID.
     *
     * @param int $id
     * @return bool True on success, false on failure.
     */
    public function delete(int $id): bool
    {
        if (empty($this->table)) {
            throw new \LogicException(get_class($this) . " must have a \$table property defined.");
        }
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            // Log error: error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Sanitize output to prevent XSS.
     * Consider if this belongs in Model or should be strictly Controller/View concern.
     * For now, providing a helper if needed by model logic (e.g. before returning data).
     *
     * @param string|null $data
     * @return string
     */
    protected function sanitize(?string $data): string
    {
        return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
    }
}
