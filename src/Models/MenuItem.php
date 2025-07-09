<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class MenuItem extends Model
{
    protected string $table = 'menu_items';

    /**
     * Create a new menu item.
     *
     * @param int $categoryId
     * @param string $name
     * @param float $price
     * @param string|null $description
     * @param string|null $imageUrl
     * @param bool $isAvailable
     * @return string|false The ID of the newly created menu item, or false on failure.
     */
    public function createMenuItem(
        int $categoryId,
        string $name,
        float $price,
        ?string $description = null,
        ?string $imageUrl = null,
        bool $isAvailable = true
    ): string|false {
        if (empty(trim($name))) {
            error_log("Menu item name cannot be empty.");
            return false;
        }
        if ($price < 0) {
            error_log("Menu item price cannot be negative.");
            return false;
        }

        $data = [
            'category_id' => $categoryId,
            'name' => $name,
            'price' => $price,
            'description' => $description,
            'image_url' => $imageUrl,
            'is_available' => (int)$isAvailable, // Cast boolean to int for DB
        ];
        return $this->create($data);
    }

    /**
     * Update an existing menu item.
     *
     * @param int $menuItemId
     * @param array $data Associative array of fields to update.
     *                    Expected keys: category_id, name, price, description, image_url, is_available.
     * @return bool True on success, false on failure.
     */
    public function updateMenuItem(int $menuItemId, array $data): bool
    {
        // Basic validation for required fields if present in $data
        if (isset($data['name']) && empty(trim($data['name']))) {
            error_log("Menu item name cannot be empty for update.");
            return false;
        }
        if (isset($data['price']) && (float)$data['price'] < 0) {
            error_log("Menu item price cannot be negative for update.");
            return false;
        }
        if (isset($data['is_available'])) {
            $data['is_available'] = (int)$data['is_available'];
        }

        return $this->update($menuItemId, $data);
    }

    /**
     * Find all menu items belonging to a specific category.
     *
     * @param int $categoryId
     * @param bool $availableOnly If true, only returns items where is_available = 1.
     * @param string $orderBy SQL ORDER BY clause
     * @return array
     */
    public function findByCategoryId(int $categoryId, bool $availableOnly = false, string $orderBy = 'name ASC'): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE category_id = :category_id";
        $params = [':category_id' => $categoryId];

        if ($availableOnly) {
            $sql .= " AND is_available = 1";
        }

        // Basic sanitization for order by
        $orderBy = preg_replace('/[^a-zA-Z0-9_ ,DESCASC]/', '', $orderBy);
        if (!empty($orderBy)) {
            $sql .= " ORDER BY " . $orderBy;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a menu item by its name (and optionally category).
     * This is just an example; names might not be unique across categories.
     *
     * @param string $name
     * @param int|null $categoryId Optional category ID to narrow search.
     * @return array|false
     */
    public function findByName(string $name, ?int $categoryId = null): array|false
    {
        $sql = "SELECT * FROM {$this->table} WHERE name = :name";
        $params = [':name' => $name];

        if ($categoryId !== null) {
            $sql .= " AND category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }
        $sql .= " LIMIT 1"; // Assuming we want one if multiple exist (e.g. across categories)

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all menu items, optionally joining with category information.
     *
     * @param bool $joinCategory If true, joins with categories table to include category_name.
     * @param string|null $orderBy SQL ORDER BY clause (e.g., 'c.name ASC, mi.name ASC')
     * @return array
     */
    public function getAllMenuItems(bool $joinCategory = false, ?string $orderBy = 'mi.name ASC'): array
    {
        if ($joinCategory) {
            $sql = "SELECT mi.*, c.name as category_name
                    FROM {$this->table} mi
                    JOIN categories c ON mi.category_id = c.id";
        } else {
            $sql = "SELECT mi.* FROM {$this->table} mi";
        }

        if ($orderBy) {
            // Basic sanitization for order by
            $orderBy = preg_replace('/[^a-zA-Z0-9_.,\sDESCASCmi c]/', '', $orderBy); // Allow mi. and c. prefixes
            $sql .= " ORDER BY " . $orderBy;
        }

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
