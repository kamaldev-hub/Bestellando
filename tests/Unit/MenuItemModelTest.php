<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\MenuItem;
use App\Models\Category;

class MenuItemModelTest extends TestCase
{
    private MenuItem $menuItemModel;
    private Category $categoryModel;
    private int $testCategoryId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->menuItemModel = new MenuItem();
        $this->categoryModel = new Category();

        // Create a default category for menu items
        $categoryId = $this->categoryModel->createCategory('Test Menu Category');
        $this->assertIsString($categoryId, "Setup: Failed to create test category.");
        $this->testCategoryId = (int)$categoryId;
    }

    public function testCreateMenuItem(): void
    {
        $name = 'Test Item Alpha';
        $price = 15.99;
        $description = 'Delicious test item.';
        $imageUrl = 'http://example.com/image.jpg';
        $isAvailable = true;

        $menuItemId = $this->menuItemModel->createMenuItem(
            $this->testCategoryId,
            $name,
            $price,
            $description,
            $imageUrl,
            $isAvailable
        );

        $this->assertIsString($menuItemId);
        $this->assertNotEmpty($menuItemId);

        $item = $this->menuItemModel->find((int)$menuItemId);
        $this->assertIsArray($item);
        $this->assertEquals($this->testCategoryId, $item['category_id']);
        $this->assertEquals($name, $item['name']);
        $this->assertEquals($price, (float)$item['price']);
        $this->assertEquals($description, $item['description']);
        $this->assertEquals($imageUrl, $item['image_url']);
        $this->assertEquals((int)$isAvailable, $item['is_available']);
    }

    public function testCreateMenuItemFailsWithEmptyName(): void
    {
        $menuItemId = $this->menuItemModel->createMenuItem($this->testCategoryId, '', 10.00);
        $this->assertFalse($menuItemId);
    }

    public function testCreateMenuItemFailsWithNegativePrice(): void
    {
        $menuItemId = $this->menuItemModel->createMenuItem($this->testCategoryId, 'Negative Price Item', -5.00);
        $this->assertFalse($menuItemId);
    }

    public function testUpdateMenuItem(): void
    {
        $menuItemId = (int)$this->menuItemModel->createMenuItem($this->testCategoryId, 'Initial Item', 10.00);

        $updateData = [
            'name' => 'Updated Item Name',
            'price' => 12.50,
            'description' => 'Updated description.',
            'is_available' => 0, // false
        ];
        $result = $this->menuItemModel->updateMenuItem($menuItemId, $updateData);
        $this->assertTrue($result);

        $updatedItem = $this->menuItemModel->find($menuItemId);
        $this->assertEquals($updateData['name'], $updatedItem['name']);
        $this->assertEquals($updateData['price'], (float)$updatedItem['price']);
        $this->assertEquals($updateData['description'], $updatedItem['description']);
        $this->assertEquals(0, $updatedItem['is_available']);
    }

    public function testUpdateMenuItemPartial(): void
    {
        $initialName = 'Partial Update Item';
        $initialPrice = 20.00;
        $menuItemId = (int)$this->menuItemModel->createMenuItem($this->testCategoryId, $initialName, $initialPrice);

        $updateData = ['price' => 22.75]; // Only update price
        $result = $this->menuItemModel->updateMenuItem($menuItemId, $updateData);
        $this->assertTrue($result);

        $updatedItem = $this->menuItemModel->find($menuItemId);
        $this->assertEquals($initialName, $updatedItem['name']); // Name should remain unchanged
        $this->assertEquals(22.75, (float)$updatedItem['price']);
    }


    public function testFindByCategoryId(): void
    {
        $catIdA = $this->testCategoryId;
        $catIdB = (int)$this->categoryModel->createCategory('Category B for Items');

        $this->menuItemModel->createMenuItem($catIdA, 'Item A1', 5.00);
        $this->menuItemModel->createMenuItem($catIdA, 'Item A2', 6.00, null, null, false); // Unavailable
        $this->menuItemModel->createMenuItem($catIdB, 'Item B1', 7.00);

        // Find all for Category A
        $itemsCatAAll = $this->menuItemModel->findByCategoryId($catIdA, false);
        $this->assertCount(2, $itemsCatAAll);

        // Find available for Category A
        $itemsCatAAvailable = $this->menuItemModel->findByCategoryId($catIdA, true);
        $this->assertCount(1, $itemsCatAAvailable);
        $this->assertEquals('Item A1', $itemsCatAAvailable[0]['name']);

        // Find for Category B
        $itemsCatB = $this->menuItemModel->findByCategoryId($catIdB);
        $this->assertCount(1, $itemsCatB);
        $this->assertEquals('Item B1', $itemsCatB[0]['name']);
    }

    public function testFindByName(): void
    {
        $itemName = "Findable Item " . uniqid();
        $this->menuItemModel->createMenuItem($this->testCategoryId, $itemName, 10.00);

        $foundItem = $this->menuItemModel->findByName($itemName);
        $this->assertIsArray($foundItem);
        $this->assertEquals($itemName, $foundItem['name']);

        // Test with category ID
        $foundItemWithCat = $this->menuItemModel->findByName($itemName, $this->testCategoryId);
        $this->assertIsArray($foundItemWithCat);
        $this->assertEquals($this->testCategoryId, $foundItemWithCat['category_id']);

        $catOtherId = (int)$this->categoryModel->createCategory('Other Category');
        $notFoundInOtherCat = $this->menuItemModel->findByName($itemName, $catOtherId);
        $this->assertFalse($notFoundInOtherCat);

        $notFoundItem = $this->menuItemModel->findByName("NonExistentItem" . uniqid());
        $this->assertFalse($notFoundItem);
    }

    public function testGetAllMenuItems(): void
    {
        // Clear existing items for a cleaner test or ensure distinct names
        // For simplicity, we rely on setUp creating a fresh DB state via transactions.

        $catIdAlpha = (int)$this->categoryModel->createCategory('Alpha Category');
        $catIdBeta = (int)$this->categoryModel->createCategory('Beta Category');

        $this->menuItemModel->createMenuItem($catIdAlpha, 'Alpha Item 1', 1.00);
        $this->menuItemModel->createMenuItem($catIdBeta, 'Beta Item 1', 2.00);

        // Get all without join
        $allItemsSimple = $this->menuItemModel->getAllMenuItems(false);
        // Number of items will be at least 2 + any from schema.sql + any from setUp.
        // Let's check if our items are present.
        $foundAlpha = false;
        $foundBeta = false;
        foreach ($allItemsSimple as $item) {
            if ($item['name'] === 'Alpha Item 1') $foundAlpha = true;
            if ($item['name'] === 'Beta Item 1') $foundBeta = true;
        }
        $this->assertTrue($foundAlpha, "Alpha Item 1 not found in getAllMenuItems(false)");
        $this->assertTrue($foundBeta, "Beta Item 1 not found in getAllMenuItems(false)");

        // Get all with join
        $allItemsJoined = $this->menuItemModel->getAllMenuItems(true, 'c.name ASC, mi.name ASC');
        $foundAlphaJoined = false;
        $foundBetaJoined = false;
        foreach ($allItemsJoined as $item) {
            if ($item['name'] === 'Alpha Item 1' && $item['category_name'] === 'Alpha Category') {
                $foundAlphaJoined = true;
            }
            if ($item['name'] === 'Beta Item 1' && $item['category_name'] === 'Beta Category') {
                $foundBetaJoined = true;
            }
        }
        $this->assertTrue($foundAlphaJoined, "Alpha Item 1 with category name not found.");
        $this->assertTrue($foundBetaJoined, "Beta Item 1 with category name not found.");

        // Check ordering if possible (tricky without knowing exact existing data)
        if (count($allItemsJoined) >= 2) {
             // Assuming 'Alpha Category' sorts before 'Beta Category'
            $this->assertLessThanOrEqual(
                0,
                strcmp($allItemsJoined[0]['category_name'], $allItemsJoined[count($allItemsJoined)-1]['category_name'])
            );
        }
    }
}
