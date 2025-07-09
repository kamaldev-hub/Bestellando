<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Category;
use App\Models\MenuItem; // Needed for testing findAllWithMenuItems

class CategoryModelTest extends TestCase
{
    private Category $categoryModel;
    private MenuItem $menuItemModel; // For setting up test menu items

    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryModel = new Category();
        $this->menuItemModel = new MenuItem(); // Instantiate for test data setup
    }

    public function testCreateCategory(): void
    {
        $name = 'Test Category Alpha';
        $description = 'A test category description.';
        $categoryId = $this->categoryModel->createCategory($name, $description);

        $this->assertIsString($categoryId);
        $this->assertNotEmpty($categoryId);

        $category = $this->categoryModel->find((int)$categoryId);
        $this->assertIsArray($category);
        $this->assertEquals($name, $category['name']);
        $this->assertEquals($description, $category['description']);
    }

    public function testCreateCategoryFailsWithEmptyName(): void
    {
        $categoryId = $this->categoryModel->createCategory('');
        $this->assertFalse($categoryId);
    }

    public function testUpdateCategory(): void
    {
        $categoryId = (int)$this->categoryModel->createCategory('Initial Category Name');

        $newName = 'Updated Category Name';
        $newDescription = 'Updated description here.';
        $result = $this->categoryModel->updateCategory($categoryId, $newName, $newDescription);
        $this->assertTrue($result);

        $updatedCategory = $this->categoryModel->find($categoryId);
        $this->assertEquals($newName, $updatedCategory['name']);
        $this->assertEquals($newDescription, $updatedCategory['description']);
    }

    public function testUpdateCategoryNameToEmptyFails(): void
    {
        $categoryId = (int)$this->categoryModel->createCategory('Valid Name');
        $result = $this->categoryModel->updateCategory($categoryId, '   ', 'Description should not change');
        $this->assertFalse($result);

        $category = $this->categoryModel->find($categoryId);
        $this->assertEquals('Valid Name', $category['name']); // Name should not have changed
    }


    public function testFindAllWithMenuItems(): void
    {
        // Create categories
        $catId1 = (int)$this->categoryModel->createCategory('Category A');
        $catId2 = (int)$this->categoryModel->createCategory('Category B');

        // Create menu items for these categories
        $this->menuItemModel->createMenuItem($catId1, 'Item A1', 10.00);
        $this->menuItemModel->createMenuItem($catId1, 'Item A2', 12.00, null, null, false); // Unavailable
        $this->menuItemModel->createMenuItem($catId2, 'Item B1', 15.00);

        // Test fetching all items (available and unavailable)
        $categoriesWithAllItems = $this->categoryModel->findAllWithMenuItems(false);
        $this->assertIsArray($categoriesWithAllItems);

        $foundCatA_all = false;
        $foundCatB_all = false;
        foreach($categoriesWithAllItems as $cat) {
            if ($cat['id'] == $catId1) {
                $foundCatA_all = true;
                $this->assertCount(2, $cat['menu_items']);
            }
            if ($cat['id'] == $catId2) {
                $foundCatB_all = true;
                $this->assertCount(1, $cat['menu_items']);
            }
        }
        $this->assertTrue($foundCatA_all, "Category A with all items not found.");
        $this->assertTrue($foundCatB_all, "Category B with all items not found.");

        // Test fetching only available items
        $categoriesWithAvailableItems = $this->categoryModel->findAllWithMenuItems(true);
        $this->assertIsArray($categoriesWithAvailableItems);

        $foundCatA_avail = false;
        $foundCatB_avail = false;
        foreach($categoriesWithAvailableItems as $cat) {
            if ($cat['id'] == $catId1) {
                $foundCatA_avail = true;
                $this->assertCount(1, $cat['menu_items'], "Category A should only have 1 available item.");
                $this->assertEquals('Item A1', $cat['menu_items'][0]['name']);
            }
            if ($cat['id'] == $catId2) {
                $foundCatB_avail = true;
                $this->assertCount(1, $cat['menu_items'], "Category B should have 1 available item.");
            }
        }
        $this->assertTrue($foundCatA_avail, "Category A with available items not found.");
        $this->assertTrue($foundCatB_avail, "Category B with available items not found.");
    }

    public function testFindByName(): void
    {
        $catName = "Findable Category " . uniqid();
        $this->categoryModel->createCategory($catName);

        $foundCategory = $this->categoryModel->findByName($catName);
        $this->assertIsArray($foundCategory);
        $this->assertEquals($catName, $foundCategory['name']);

        $notFoundCategory = $this->categoryModel->findByName("NonExistentCat" . uniqid());
        $this->assertFalse($notFoundCategory);
    }

    // Test base model methods like find, findAll, delete
    public function testBaseModelMethods(): void
    {
        // Create
        $catId = $this->categoryModel->create(['name' => 'Base Test Cat', 'description' => 'Desc']);
        $this->assertIsString($catId);
        $createdCat = $this->categoryModel->find((int)$catId);
        $this->assertEquals('Base Test Cat', $createdCat['name']);

        // findAll (check if our new one is among them)
        $allCats = $this->categoryModel->findAll();
        $foundInAll = false;
        foreach ($allCats as $cat) {
            if ($cat['id'] == $catId) {
                $foundInAll = true;
                break;
            }
        }
        $this->assertTrue($foundInAll);

        // Delete
        $deleteResult = $this->categoryModel->delete((int)$catId);
        $this->assertTrue($deleteResult);
        $this->assertFalse($this->categoryModel->find((int)$catId));
    }
}
