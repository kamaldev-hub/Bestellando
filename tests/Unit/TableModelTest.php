<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Table;
use PDO; // For type hinting if directly using PDO methods for setup

class TableModelTest extends TestCase
{
    private Table $tableModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tableModel = new Table();
        // The schema.sql already inserts some tables.
        // We can rely on those or add specific ones for tests.
    }

    public function testGetAllTablesReturnsArray(): void
    {
        $tables = $this->tableModel->getAllTables();
        $this->assertIsArray($tables);
        // Based on schema.sql, there should be some tables
        $this->assertNotEmpty($tables, "Should retrieve tables from schema.sql sample data.");
    }

    public function testGetAllTablesFilteredByStatus(): void
    {
        // Assuming schema.sql has 'available' tables
        $availableTables = $this->tableModel->getAllTables(Table::STATUS_AVAILABLE);
        $this->assertIsArray($availableTables);
        $this->assertNotEmpty($availableTables);
        foreach ($availableTables as $table) {
            $this->assertEquals(Table::STATUS_AVAILABLE, $table['status']);
        }

        // Create a table with 'occupied' status for testing this filter
        $this->getPdo()->exec("INSERT INTO restaurant_tables (table_number, capacity, status) VALUES ('T99', 2, '".Table::STATUS_OCCUPIED."')");

        $occupiedTables = $this->tableModel->getAllTables(Table::STATUS_OCCUPIED);
        $this->assertIsArray($occupiedTables);
        $this->assertNotEmpty($occupiedTables);
        $foundT99 = false;
        foreach ($occupiedTables as $table) {
            $this->assertEquals(Table::STATUS_OCCUPIED, $table['status']);
            if ($table['table_number'] === 'T99') {
                $foundT99 = true;
            }
        }
        $this->assertTrue($foundT99, "Newly inserted occupied table 'T99' not found with filter.");
    }

    public function testFindByTableNumber(): void
    {
        // Using a table from schema.sql
        $tableNumber = '1'; // Assumes table '1' exists from schema.sql
        $table = $this->tableModel->findByTableNumber($tableNumber);

        $this->assertIsArray($table);
        $this->assertEquals($tableNumber, $table['table_number']);
    }

    public function testFindByTableNumberReturnsFalseForNonExistent(): void
    {
        $table = $this->tableModel->findByTableNumber('X999');
        $this->assertFalse($table);
    }

    public function testUpdateStatus(): void
    {
        // Find an existing table from schema data to update
        $tableToUpdate = $this->tableModel->findByTableNumber('2'); // Table '2' from schema
        $this->assertIsArray($tableToUpdate, "Table '2' should exist for updating.");
        $tableId = (int)$tableToUpdate['id'];

        $newStatus = Table::STATUS_OCCUPIED;
        $result = $this->tableModel->updateStatus($tableId, $newStatus);
        $this->assertTrue($result, "updateStatus should return true on success.");

        $updatedTable = $this->tableModel->find($tableId);
        $this->assertEquals($newStatus, $updatedTable['status']);
    }

    public function testUpdateStatusFailsWithInvalidStatus(): void
    {
        $tableToUpdate = $this->tableModel->findByTableNumber('3'); // Table '3'
        $this->assertIsArray($tableToUpdate);
        $tableId = (int)$tableToUpdate['id'];

        $result = $this->tableModel->updateStatus($tableId, 'non_existent_status');
        $this->assertFalse($result, "updateStatus should return false for invalid status.");

        // Verify status hasn't changed
        $originalTable = $this->tableModel->find($tableId);
        $this->assertEquals($tableToUpdate['status'], $originalTable['status']);
    }

    public function testIsValidStatus(): void
    {
        $this->assertTrue($this->tableModel->isValidStatus(Table::STATUS_AVAILABLE));
        $this->assertTrue($this->tableModel->isValidStatus(Table::STATUS_OCCUPIED));
        $this->assertTrue($this->tableModel->isValidStatus(Table::STATUS_RESERVED));
        $this->assertFalse($this->tableModel->isValidStatus('cleaning'));
        $this->assertFalse($this->tableModel->isValidStatus(''));
    }

    public function testGetAvailableStatuses(): void
    {
        $statuses = Table::getAvailableStatuses();
        $this->assertIsArray($statuses);
        $this->assertArrayHasKey(Table::STATUS_AVAILABLE, $statuses);
        $this->assertEquals('Available', $statuses[Table::STATUS_AVAILABLE]);
    }

    // Test for base Model methods like find, findAll, create, update, delete if not covered
    public function testBaseModelFind(): void
    {
        $tableFromSchema = $this->tableModel->findByTableNumber('4'); // Table '4' from schema
        $this->assertIsArray($tableFromSchema);

        $foundById = $this->tableModel->find((int)$tableFromSchema['id']);
        $this->assertIsArray($foundById);
        $this->assertEquals($tableFromSchema['table_number'], $foundById['table_number']);
    }

    public function testBaseModelCreateAndUpdateAndDelete(): void
    {
        // Create
        $tableNumber = 'TCR';
        $capacity = 3;
        $status = Table::STATUS_AVAILABLE;
        $tableId = $this->tableModel->create([
            'table_number' => $tableNumber,
            'capacity' => $capacity,
            'status' => $status
        ]);
        $this->assertIsString($tableId);
        $createdTable = $this->tableModel->find((int)$tableId);
        $this->assertEquals($tableNumber, $createdTable['table_number']);

        // Update
        $newCapacity = 4;
        $updateResult = $this->tableModel->update((int)$tableId, ['capacity' => $newCapacity, 'status' => Table::STATUS_RESERVED]);
        $this->assertTrue($updateResult);
        $updatedTable = $this->tableModel->find((int)$tableId);
        $this->assertEquals($newCapacity, $updatedTable['capacity']);
        $this->assertEquals(Table::STATUS_RESERVED, $updatedTable['status']);

        // Delete
        $deleteResult = $this->tableModel->delete((int)$tableId);
        $this->assertTrue($deleteResult);
        $deletedTable = $this->tableModel->find((int)$tableId);
        $this->assertFalse($deletedTable);
    }
}
