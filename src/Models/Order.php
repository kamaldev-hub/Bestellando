<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Order extends Model
{
    protected string $table = 'orders';

    // Order statuses
    public const STATUS_NEW = 'new';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    public const STATUS_COMPLETED = 'completed'; // Bill paid, order closed
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_BILLING = 'billing'; // Bill presented, awaiting payment

    /**
     * Create a new order.
     *
     * @param int $tableId
     * @param int $userId (waiter ID)
     * @param string $status
     * @param float $totalAmount (initial, might be updated later)
     * @param string|null $notes
     * @return string|false The ID of the newly created order, or false on failure.
     */
    public function createOrder(int $tableId, int $userId, string $status = self::STATUS_NEW, float $totalAmount = 0.00, ?string $notes = null): string|false
    {
        if (!$this->isValidStatus($status)) {
            error_log("Invalid order status provided: {$status}");
            return false;
        }

        $data = [
            'table_id' => $tableId,
            'user_id' => $userId,
            'status' => $status,
            'total_amount' => $totalAmount,
            'notes' => $notes,
        ];
        return $this->create($data);
    }

    /**
     * Find an order by its ID, including its items and table information.
     *
     * @param int $orderId
     * @return array|false Order data with items, or false if not found.
     */
    public function findWithDetails(int $orderId): array|false
    {
        $sql = "SELECT o.*, t.table_number
                FROM {$this->table} o
                JOIN restaurant_tables t ON o.table_id = t.id
                WHERE o.id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $orderItemModel = new OrderItem();
            $order['items'] = $orderItemModel->findByOrderId($orderId);
            // Calculate total amount if not stored or to verify
            $order['calculated_total'] = $this->calculateOrderTotal($orderId);
        }
        return $order;
    }

    /**
     * Get all orders, possibly filtered by status, with table and waiter info.
     *
     * @param string|null $status
     * @param string $orderBy SQL ORDER BY clause
     * @return array
     */
    public function getAllOrdersWithDetails(?string $status = null, string $orderBy = 'o.created_at DESC'): array
    {
        $sql = "SELECT o.*, t.table_number, u.username as waiter_username
                FROM {$this->table} o
                JOIN restaurant_tables t ON o.table_id = t.id
                LEFT JOIN users u ON o.user_id = u.id";

        $params = [];
        if ($status && $this->isValidStatus($status)) {
            $sql .= " WHERE o.status = :status";
            $params[':status'] = $status;
        }

        // Sanitize orderBy slightly - for internal use, ensure it's valid columns
        $orderBy = preg_replace('/[^a-zA-Z0-9_.,\sDESCASC]/', '', $orderBy);
        $sql .= " ORDER BY " . $orderBy;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch items for each order
        $orderItemModel = new OrderItem();
        foreach ($orders as &$order) {
            $order['items'] = $orderItemModel->findByOrderId((int)$order['id']);
            $order['calculated_total'] = $this->calculateOrderTotal((int)$order['id']);
        }

        return $orders;
    }

    /**
     * Get active orders for a specific table (not completed or cancelled).
     * @param int $tableId
     * @return array|false The active order or false if none.
     */
    public function getActiveOrderByTable(int $tableId): array|false
    {
        $sql = "SELECT o.*
                FROM {$this->table} o
                WHERE o.table_id = :table_id
                AND o.status NOT IN (:status_completed, :status_cancelled)
                ORDER BY o.created_at DESC
                LIMIT 1"; // Assuming one active order per table

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':table_id' => $tableId,
            ':status_completed' => self::STATUS_COMPLETED,
            ':status_cancelled' => self::STATUS_CANCELLED
        ]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $orderItemModel = new OrderItem();
            $order['items'] = $orderItemModel->findByOrderId((int)$order['id']);
            $order['calculated_total'] = $this->calculateOrderTotal((int)$order['id']);
        }
        return $order;
    }


    /**
     * Update the status of an order.
     *
     * @param int $orderId
     * @param string $newStatus
     * @return bool
     */
    public function updateStatus(int $orderId, string $newStatus): bool
    {
        if (!$this->isValidStatus($newStatus)) {
            error_log("Invalid order status provided: {$newStatus}");
            return false;
        }
        return $this->update($orderId, ['status' => $newStatus]);
    }

    /**
     * Calculate the total amount for an order based on its items.
     *
     * @param int $orderId
     * @return float
     */
    public function calculateOrderTotal(int $orderId): float
    {
        $orderItemModel = new OrderItem();
        $items = $orderItemModel->findByOrderId($orderId);
        $total = 0.00;
        foreach ($items as $item) {
            $total += $item['price_at_order'] * $item['quantity'];
        }
        return (float) $total;
    }

    /**
     * Updates the total_amount field in the orders table.
     *
     * @param int $orderId
     * @return bool
     */
    public function updateTotalAmount(int $orderId): bool
    {
        $calculatedTotal = $this->calculateOrderTotal($orderId);
        return $this->update($orderId, ['total_amount' => $calculatedTotal]);
    }

    /**
     * Check if a status string is valid.
     * @param string $status
     * @return bool
     */
    public function isValidStatus(string $status): bool
    {
        return in_array($status, [
            self::STATUS_NEW,
            self::STATUS_IN_PROGRESS,
            self::STATUS_READY_FOR_PICKUP,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_BILLING
        ], true);
    }

    /**
     * Get all available status types.
     * @return array
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_READY_FOR_PICKUP => 'Ready for Pickup',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_BILLING => 'Billing'
        ];
    }
}
