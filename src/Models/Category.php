<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Category extends Model
{
    protected string $table = 'categories';

    /**
     * Create a new category.
     *
     * @param string $name Category name.
     * @param string|null $description Optional description.
     * @return string|false The ID of the newly created category, or false on failure.
     */
    public function createCategory(string $name, ?string $description = null): string|false
    {
        // Basic validation (e.g., name is required) can be added here or in controller
        if (empty(trim($name))) {
            // Consider throwing an InvalidArgumentException
            error_log("Category name cannot be empty.");
            return false;
        }

        $data = [
            'name' => $name,
            'description' => $description,
        ];
        return $this->create($data);
    }

    /**
     * Update an existing category.
     *
     * @param int $categoryId The ID of the category to update.
     * @param string $name New name for the category.
     * @param string|null $description Optional new description.
     * @return bool True on success, false on failure.
     */
    public function updateCategory(int $categoryId, string $name, ?string $description = null): bool
    {
        if (empty(trim($name))) {
            error_log("Category name cannot be empty for update.");
            return false;
        }
        $data = ['name' => $name];
        // Only include description in update if it's provided (allows clearing it with empty string)
        if ($description !== null) {
            $data['description'] = $description;
        }
        return $this->update($categoryId, $data);
    }

    /**
     * Get all categories, with their associated menu items.
     * Menu items are ordered by name by default.
     *
     * @param bool $availableItemsOnly If true, only fetches menu items where is_available = 1.
     * @return array
     */
    public function findAllWithMenuItems(bool $availableItemsOnly = false): array
    {
        $categories = $this->findAll('name ASC'); // Order categories by name
        $menuItemModel = new MenuItem();

        foreach ($categories as &$category) {
            $category['menu_items'] = $menuItemModel->findByCategoryId((int)$category['id'], $availableItemsOnly);
        }
        return $categories;
    }

    /**
     * Find a category by its name.
     *
     * @param string $name
     * @return array|false
     */
    public function findByName(string $name): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE name = :name");
        $stmt->bindParam(':name', $name);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
