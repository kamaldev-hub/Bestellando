<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\User;
use App\Models\MenuItem; // For creating menu items needed for orders
use App\Models\Category; // For creating categories for menu items

class OrderModelTest extends TestCase
{
    private Order $orderModel;
    private Table $tableModel;
    private User $userModel;
    private MenuItem $menuItemModel;
    private Category $categoryModel;

    private int $testTableId;
    private int $testWaiterId;
    private int $testMenuItemId1;
    private float $testMenuItem1Price;
    private int $testMenuItemId2;
    private float $testMenuItem2Price;


    protected function setUp(): void
    {
        parent::setUp();
        $this->orderModel = new Order();
        $this->tableModel = new Table();
        $this->userModel = new User();
        $this->menuItemModel = new MenuItem();
        $this->categoryModel = new Category();

        // Setup common prerequisite data for order tests
        $tableId = $this->tableModel->create(['table_number' => 'TORD1', 'capacity' => 2, 'status' => Table::STATUS_AVAILABLE]);
        $this->testTableId = (int)$tableId;

        $waiterId = $this->userModel->createUser('order_waiter', 'password', User::ROLE_WAITER);
        $this->testWaiterId = (int)$waiterId;

        $categoryId = $this->categoryModel->create(['name' => 'Order Test Cat']);
        $this->assertIsString($categoryId, "Failed to create category for order tests.");

        $menuItem1Id = $this->menuItemModel->create([
            'category_id' => (int)$categoryId,
            'name' => 'Order Test Item 1',
            'price' => 10.50,
            'is_available' => 1
        ]);
        $this->assertIsString($menuItem1Id, "Failed to create menu item 1 for order tests.");
        $this->testMenuItemId1 = (int)$menuItem1Id;
        $this->testMenuItem1Price = 10.50;

        $menuItem2Id = $this->menuItemModel->create([
            'category_id' => (int)$categoryId,
            'name' => 'Order Test Item 2',
            'price' => 5.25,
            'is_available' => 1
        ]);
        $this->assertIsString($menuItem2Id, "Failed to create menu item 2 for order tests.");
        $this->testMenuItemId2 = (int)$menuItem2Id;
        $this->testMenuItem2Price = 5.25;
    }

    public function testCreateOrder(): void
    {
        $orderId = $this->orderModel->createOrder(
            $this->testTableId,
            $this->testWaiterId,
            Order::STATUS_NEW,
            0.00,
            'Test order notes'
        );

        $this->assertIsString($orderId);
        $this->assertNotEmpty($orderId);

        $order = $this->orderModel->find((int)$orderId);
        $this->assertIsArray($order);
        $this->assertEquals($this->testTableId, $order['table_id']);
        $this->assertEquals($this->testWaiterId, $order['user_id']);
        $this->assertEquals(Order::STATUS_NEW, $order['status']);
        $this->assertEquals('Test order notes', $order['notes']);
    }

    public function testCreateOrderFailsWithInvalidStatus(): void
    {
        $orderId = $this->orderModel->createOrder($this->testTableId, $this->testWaiterId, 'invalid_status');
        $this->assertFalse($orderId);
    }

    public function testFindWithDetails(): void
    {
        $orderId = (int)$this->orderModel->createOrder($this->testTableId, $this->testWaiterId);
        $orderItemModel = new OrderItem();
        $orderItemModel->addItem($orderId, $this->testMenuItemId1, 2, $this->testMenuItem1Price, 'Item 1 notes');

        $order = $this->orderModel->findWithDetails($orderId);

        $this->assertIsArray($order);
        $this->assertEquals($orderId, $order['id']);
        $this->assertArrayHasKey('items', $order);
        $this->assertCount(1, $order['items']);
        $this->assertEquals($this->testMenuItemId1, $order['items'][0]['menu_item_id']);
        $this->assertEquals('Item 1 notes', $order['items'][0]['notes']);
        $this->assertEquals(2 * $this->testMenuItem1Price, $order['calculated_total']);
    }

    public function testGetActiveOrderByTable(): void
    {
        // Create an active order
        $orderIdActive = $this->orderModel->createOrder($this->testTableId, $this->testWaiterId, Order::STATUS_IN_PROGRESS);
        $this->assertIsString($orderIdActive);
        $orderItemModel = new OrderItem();
        $orderItemModel->addItem((int)$orderIdActive, $this->testMenuItemId1, 1, $this->testMenuItem1Price);

        // Create a completed order for the same table (should be ignored)
        $orderIdCompleted = $this->orderModel->createOrder($this->testTableId, $this->testWaiterId, Order::STATUS_COMPLETED);
        $this->assertIsString($orderIdCompleted);


        $activeOrder = $this->orderModel->getActiveOrderByTable($this->testTableId);
        $this->assertIsArray($activeOrder);
        $this->assertEquals($orderIdActive, $activeOrder['id']);
        $this->assertEquals(Order::STATUS_IN_PROGRESS, $activeOrder['status']);
        $this->assertCount(1, $activeOrder['items']);
    }

    public function testGetActiveOrderByTableReturnsFalseIfNone(): void
    {
        $newTableId = (int)$this->tableModel->create(['table_number' => 'TNONE', 'capacity' => 2]);
        $activeOrder = $this->orderModel->getActiveOrderByTable($newTableId);
        $this->assertFalse($activeOrder);
    }


    public function testUpdateStatus(): void
    {
        $orderId = (int)$this->orderModel->createOrder($this->testTableId, $this->testWaiterId);
        $result = $this->orderModel->updateStatus($orderId, Order::STATUS_IN_PROGRESS);
        $this->assertTrue($result);

        $order = $this->orderModel->find($orderId);
        $this->assertEquals(Order::STATUS_IN_PROGRESS, $order['status']);
    }

    public function testUpdateStatusFailsWithInvalidStatus(): void
    {
        $orderId = (int)$this->orderModel->createOrder($this->testTableId, $this->testWaiterId);
        $result = $this->orderModel->updateStatus($orderId, 'bad_status');
        $this->assertFalse($result);
    }

    public function testCalculateOrderTotal(): void
    {
        $orderId = (int)$this->orderModel->createOrder($this->testTableId, $this->testWaiterId);
        $orderItemModel = new OrderItem();
        $orderItemModel->addItem($orderId, $this->testMenuItemId1, 2, $this->testMenuItem1Price); // 2 * 10.50 = 21.00
        $orderItemModel->addItem($orderId, $this->testMenuItemId2, 1, $this->testMenuItem2Price); // 1 * 5.25 = 5.25
                                                                                      // Total = 26.25
        $total = $this->orderModel->calculateOrderTotal($orderId);
        $this->assertEquals(26.25, $total);
    }

    public function testUpdateTotalAmount(): void
    {
        $orderId = (int)$this->orderModel->createOrder($this->testTableId, $this->testWaiterId, Order::STATUS_NEW, 0.00);
        $orderItemModel = new OrderItem();
        $orderItemModel->addItem($orderId, $this->testMenuItemId1, 3, $this->testMenuItem1Price); // 3 * 10.50 = 31.50

        $updateResult = $this->orderModel->updateTotalAmount($orderId);
        $this->assertTrue($updateResult);

        $updatedOrder = $this->orderModel->find($orderId);
        $this->assertEquals(31.50, (float)$updatedOrder['total_amount']);
    }


    public function testGetAllOrdersWithDetails(): void
    {
        $orderId1 = (int)$this->orderModel->createOrder($this->testTableId, $this->testWaiterId, Order::STATUS_NEW);
        $orderItemModel = new OrderItem();
        $orderItemModel->addItem($orderId1, $this->testMenuItemId1, 1, $this->testMenuItem1Price);

        $tableId2 = (int)$this->tableModel->create(['table_number' => 'TORD2', 'capacity' => 4]);
        $waiterId2 = (int)$this->userModel->createUser('order_waiter2', 'password', User::ROLE_WAITER);
        $orderId2 = (int)$this->orderModel->createOrder($tableId2, $waiterId2, Order::STATUS_IN_PROGRESS);
        $orderItemModel->addItem($orderId2, $this->testMenuItemId2, 2, $this->testMenuItem2Price);

        $allOrders = $this->orderModel->getAllOrdersWithDetails();
        $this->assertIsArray($allOrders);
        $this->assertGreaterThanOrEqual(2, count($allOrders)); // Expect at least the two we created

        $foundOrder1 = false;
        $foundOrder2 = false;
        foreach($allOrders as $order) {
            if ($order['id'] == $orderId1) {
                $foundOrder1 = true;
                $this->assertEquals(Order::STATUS_NEW, $order['status']);
                $this->assertCount(1, $order['items']);
                $this->assertEquals($this->testMenuItem1Price, $order['calculated_total']);
            }
            if ($order['id'] == $orderId2) {
                $foundOrder2 = true;
                $this->assertEquals(Order::STATUS_IN_PROGRESS, $order['status']);
                $this->assertCount(1, $order['items']); // Each addItem call creates one OrderItem row
                $this->assertEquals(2 * $this->testMenuItem2Price, $order['calculated_total']);
                $this->assertEquals('order_waiter2', $order['waiter_username']);
            }
        }
        $this->assertTrue($foundOrder1, "Order 1 not found in getAllOrdersWithDetails");
        $this->assertTrue($foundOrder2, "Order 2 not found in getAllOrdersWithDetails");

        // Test filtering by status
        $newOrders = $this->orderModel->getAllOrdersWithDetails(Order::STATUS_NEW);
        $this->assertNotEmpty($newOrders);
        foreach ($newOrders as $order) {
            $this->assertEquals(Order::STATUS_NEW, $order['status']);
        }
    }

    public function testIsValidStatus(): void
    {
        $this->assertTrue($this->orderModel->isValidStatus(Order::STATUS_NEW));
        $this->assertTrue($this->orderModel->isValidStatus(Order::STATUS_BILLING));
        $this->assertFalse($this->orderModel->isValidStatus('shipped'));
    }

    public function testGetAvailableStatuses(): void
    {
        $statuses = Order::getAvailableStatuses();
        $this->assertIsArray($statuses);
        $this->assertArrayHasKey(Order::STATUS_IN_PROGRESS, $statuses);
        $this->assertEquals('In Progress', $statuses[Order::STATUS_IN_PROGRESS]);
    }
}
