<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class OrderItem extends Model
{
    protected string $table = 'order_items';

    /**
     * Add an item to an order.
     *
     * @param int $orderId
     * @param int $menuItemId
     * @param int $quantity
     * @param float $priceAtOrder The price of the menu item at the time of ordering.
     * @param string|null $notes Optional notes for this specific item (e.g., "no onions").
     * @return string|false The ID of the newly created order item, or false on failure.
     */
    public function addItem(int $orderId, int $menuItemId, int $quantity, float $priceAtOrder, ?string $notes = null): string|false
    {
        if ($quantity <= 0) {
            // Or throw an InvalidArgumentException
            error_log("Quantity must be positive. Provided: {$quantity}");
            return false;
        }

        $data = [
            'order_id' => $orderId,
            'menu_item_id' => $menuItemId,
            'quantity' => $quantity,
            'price_at_order' => $priceAtOrder,
            'notes' => $notes,
        ];
        return $this->create($data);
    }

    /**
     * Find all items for a given order ID, including menu item details.
     *
     * @param int $orderId
     * @return array
     */
    public function findByOrderId(int $orderId): array
    {
        $sql = "SELECT oi.*, mi.name as menu_item_name, mi.description as menu_item_description
                FROM {$this->table} oi
                JOIN menu_items mi ON oi.menu_item_id = mi.id
                WHERE oi.order_id = :order_id
                ORDER BY oi.created_at ASC"; // Keep order of addition

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update quantity or notes for an existing order item.
     *
     * @param int $orderItemId
     * @param int $newQuantity
     * @param string|null $newNotes
     * @return bool
     */
    public function updateItem(int $orderItemId, int $newQuantity, ?string $newNotes = null): bool
    {
        if ($newQuantity <= 0) {
            // To remove an item, use deleteItem(). Quantity here must be positive.
            error_log("New quantity must be positive. To remove, use deleteItem. Provided: {$newQuantity}");
            return false;
        }
        $data = ['quantity' => $newQuantity];
        if (!is_null($newNotes)) { // Allow empty string for notes to clear them
            $data['notes'] = $newNotes;
        }
        return $this->update($orderItemId, $data);
    }

    /**
     * Remove an item from an order (deletes the order item record).
     *
     * @param int $orderItemId
     * @return bool
     */
    public function deleteItem(int $orderItemId): bool
    {
        return $this->delete($orderItemId);
    }

    /**
     * Check if a specific menu item already exists in an order.
     * Useful to increment quantity instead of adding a new row.
     *
     * @param int $orderId
     * @param int $menuItemId
     * @return array|false The existing order item record or false.
     */
    public function findExistingItemInOrder(int $orderId, int $menuItemId): array|false
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE order_id = :order_id AND menu_item_id = :menu_item_id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':order_id' => $orderId, ':menu_item_id' => $menuItemId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
