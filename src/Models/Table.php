<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Table extends Model
{
    protected string $table = 'restaurant_tables';

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_OCCUPIED = 'occupied';
    public const STATUS_RESERVED = 'reserved';

    /**
     * Get all tables, optionally filtered by status.
     *
     * @param string|null $status Filter by status (e.g., 'available')
     * @return array
     */
    public function getAllTables(string $status = null): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        if ($status && $this->isValidStatus($status)) {
            $sql .= " WHERE status = :status";
            $params[':status'] = $status;
        }
        $sql .= " ORDER BY table_number ASC"; // Order by table number

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a table by its table number.
     *
     * @param string $tableNumber
     * @return array|false
     */
    public function findByTableNumber(string $tableNumber): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE table_number = :table_number");
        $stmt->bindParam(':table_number', $tableNumber);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update the status of a table.
     *
     * @param int $tableId
     * @param string $newStatus
     * @return bool
     */
    public function updateStatus(int $tableId, string $newStatus): bool
    {
        if (!$this->isValidStatus($newStatus)) {
            // Or throw an exception
            error_log("Invalid table status provided: {$newStatus}");
            return false;
        }
        return $this->update($tableId, ['status' => $newStatus]);
    }

    /**
     * Check if a status string is valid.
     * @param string $status
     * @return bool
     */
    public function isValidStatus(string $status): bool
    {
        return in_array($status, [self::STATUS_AVAILABLE, self::STATUS_OCCUPIED, self::STATUS_RESERVED], true);
    }

    /**
     * Get all available status types.
     * @return array
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_OCCUPIED => 'Occupied',
            self::STATUS_RESERVED => 'Reserved'
        ];
    }

    // CRUD methods (create, update, delete for tables if needed by admin)
    // For now, focusing on what Waiter interface needs.
    // Admin might manage tables (add new tables, change capacity etc.) via a different controller.
}
