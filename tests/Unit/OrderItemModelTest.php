<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use App\Models\MenuItem;
use App\Models\Category;

class OrderItemModelTest extends TestCase
{
    private OrderItem $orderItemModel;
    private Order $orderModel; // To create parent orders

    // Prerequisite IDs
    private int $testOrderId;
    private int $testMenuItemId1;
    private float $testMenuItem1Price;
    private int $testMenuItemId2;
    private float $testMenuItem2Price;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderItemModel = new OrderItem();
        $this->orderModel = new Order();

        // Common setup from OrderModelTest for prerequisite data
        $tableModel = new Table();
        $userModel = new User();
        $menuItemModel = new MenuItem();
        $categoryModel = new Category();

        $tableId = (int)$tableModel->create(['table_number' => 'TOI1', 'capacity' => 2]);
        $waiterId = (int)$userModel->createUser('oi_waiter', 'password', User::ROLE_WAITER);
        $this->testOrderId = (int)$this->orderModel->createOrder($tableId, $waiterId);

        $categoryId = (int)$categoryModel->create(['name' => 'Order Item Test Cat']);

        $this->testMenuItemId1 = (int)$menuItemModel->create([
            'category_id' => $categoryId,
            'name' => 'OI Test Item 1',
            'price' => 12.00,
            'is_available' => 1
        ]);
        $this->testMenuItem1Price = 12.00;

        $this->testMenuItemId2 = (int)$menuItemModel->create([
            'category_id' => $categoryId,
            'name' => 'OI Test Item 2',
            'price' => 8.50,
            'is_available' => 1
        ]);
        $this->testMenuItem2Price = 8.50;
    }

    public function testAddItem(): void
    {
        $notes = 'Extra cheese';
        $orderItemId = $this->orderItemModel->addItem(
            $this->testOrderId,
            $this->testMenuItemId1,
            2,
            $this->testMenuItem1Price,
            $notes
        );

        $this->assertIsString($orderItemId);
        $this->assertNotEmpty($orderItemId);

        $item = $this->orderItemModel->find((int)$orderItemId);
        $this->assertIsArray($item);
        $this->assertEquals($this->testOrderId, $item['order_id']);
        $this->assertEquals($this->testMenuItemId1, $item['menu_item_id']);
        $this->assertEquals(2, $item['quantity']);
        $this->assertEquals($this->testMenuItem1Price, (float)$item['price_at_order']);
        $this->assertEquals($notes, $item['notes']);
    }

    public function testAddItemFailsWithZeroQuantity(): void
    {
        $orderItemId = $this->orderItemModel->addItem($this->testOrderId, $this->testMenuItemId1, 0, $this->testMenuItem1Price);
        $this->assertFalse($orderItemId);
    }

    public function testAddItemFailsWithNegativeQuantity(): void
    {
        $orderItemId = $this->orderItemModel->addItem($this->testOrderId, $this->testMenuItemId1, -1, $this->testMenuItem1Price);
        $this->assertFalse($orderItemId);
    }

    public function testFindByOrderId(): void
    {
        $this->orderItemModel->addItem($this->testOrderId, $this->testMenuItemId1, 1, $this->testMenuItem1Price);
        $this->orderItemModel->addItem($this->testOrderId, $this->testMenuItemId2, 3, $this->testMenuItem2Price, 'No onions');

        $items = $this->orderItemModel->findByOrderId($this->testOrderId);
        $this->assertIsArray($items);
        $this->assertCount(2, $items);

        // Check details of one item
        $foundItem1 = false;
        $foundItem2 = false;
        foreach($items as $item) {
            if ($item['menu_item_id'] == $this->testMenuItemId1) {
                $foundItem1 = true;
                $this->assertEquals(1, $item['quantity']);
                $this->assertEquals('OI Test Item 1', $item['menu_item_name']); // Joined name
            }
            if ($item['menu_item_id'] == $this->testMenuItemId2) {
                $foundItem2 = true;
                $this->assertEquals(3, $item['quantity']);
                $this->assertEquals('No onions', $item['notes']);
                $this->assertEquals('OI Test Item 2', $item['menu_item_name']);
            }
        }
        $this->assertTrue($foundItem1 && $foundItem2, "Both items not found by order ID");
    }

    public function testUpdateItem(): void
    {
        $orderItemId = (int)$this->orderItemModel->addItem($this->testOrderId, $this->testMenuItemId1, 1, $this->testMenuItem1Price, 'Initial note');

        $newQuantity = 3;
        $newNotes = 'Updated note, more sauce';
        $result = $this->orderItemModel->updateItem($orderItemId, $newQuantity, $newNotes);
        $this->assertTrue($result);

        $updatedItem = $this->orderItemModel->find($orderItemId);
        $this->assertEquals($newQuantity, $updatedItem['quantity']);
        $this->assertEquals($newNotes, $updatedItem['notes']);
    }

    public function testUpdateItemNotesOnly(): void
    {
        $initialQuantity = 1;
        $orderItemId = (int)$this->orderItemModel->addItem($this->testOrderId, $this->testMenuItemId1, $initialQuantity, $this->testMenuItem1Price, 'Initial note');

        $newNotes = 'Only notes updated';
        // To update only notes, we'd pass the original quantity.
        // The method signature is updateItem(int $orderItemId, int $newQuantity, ?string $newNotes = null)
        // So if notes are not provided, they are not updated. If provided (even empty string), they are.
        $result = $this->orderItemModel->updateItem($orderItemId, $initialQuantity, $newNotes);
        $this->assertTrue($result);

        $updatedItem = $this->orderItemModel->find($orderItemId);
        $this->assertEquals($initialQuantity, $updatedItem['quantity']); // Quantity should remain same
        $this->assertEquals($newNotes, $updatedItem['notes']);
    }

    public function testUpdateItemFailsWithZeroQuantity(): void
    {
        $orderItemId = (int)$this->orderItemModel->addItem($this->testOrderId, $this->testMenuItemId1, 1, $this->testMenuItem1Price);
        $result = $this->orderItemModel->updateItem($orderItemId, 0, 'some notes');
        $this->assertFalse($result);
    }

    public function testDeleteItem(): void
    {
        $orderItemId = (int)$this->orderItemModel->addItem($this->testOrderId, $this->testMenuItemId1, 1, $this->testMenuItem1Price);

        $result = $this->orderItemModel->deleteItem($orderItemId);
        $this->assertTrue($result);

        $deletedItem = $this->orderItemModel->find($orderItemId);
        $this->assertFalse($deletedItem);
    }

    public function testFindExistingItemInOrder(): void
    {
        // Add an item
        $this->orderItemModel->addItem($this->testOrderId, $this->testMenuItemId1, 1, $this->testMenuItem1Price);

        // Try to find it
        $existingItem = $this->orderItemModel->findExistingItemInOrder($this->testOrderId, $this->testMenuItemId1);
        $this->assertIsArray($existingItem);
        $this->assertEquals($this->testMenuItemId1, $existingItem['menu_item_id']);

        // Try to find a non-existing item in this order
        $nonExistingItem = $this->orderItemModel->findExistingItemInOrder($this->testOrderId, $this->testMenuItemId2); // Item 2 not added yet
        $this->assertFalse($nonExistingItem);
    }
}
