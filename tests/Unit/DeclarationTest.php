<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Tests\Unit;

use Flytachi\Winter\Cdo\Config\Common\DbConfigInterface;
use Flytachi\Winter\Edo\Declaration;
use Flytachi\Winter\Edo\DeclarationItem;
use Flytachi\Winter\Edo\Mapping\Structure\Table;
use PHPUnit\Framework\TestCase;

// ---------------------------------------------------------------------------
// Stubs
// ---------------------------------------------------------------------------

/**
 * Concrete implementation of DbConfigInterface for testing.
 * Implements all interface methods with no-op stubs.
 */
class FakeDbConfig implements DbConfigInterface
{
    public function setUp(): void
    {
    }
    public function getDns(): string
    {
        return '';
    }
    public function getPersistentStatus(): bool
    {
        return false;
    }
    public function getDriver(): string
    {
        return 'pgsql';
    }
    public function getUsername(): string
    {
        return '';
    }
    public function getPassword(): string
    {
        return '';
    }
    public function connect(int $timeout = 3): void
    {
    }
    public function disconnect(): void
    {
    }
    public function reconnect(): void
    {
    }
    public function connection(): \Flytachi\Winter\Cdo\Connection\CDO
    {
        return $this->createMock(\Flytachi\Winter\Cdo\Connection\CDO::class);
    }
    public function ping(): bool
    {
        return true;
    }
    public function pingDetail(): array
    {
        return [];
    }
    public function getSchema(): ?string
    {
        return null;
    }
}

/**
 * Second config class — distinct type for merge-isolation tests.
 */
class AnotherFakeDbConfig extends FakeDbConfig
{
}

// ---------------------------------------------------------------------------
// DeclarationItem tests
// ---------------------------------------------------------------------------

class DeclarationItemTest extends TestCase
{
    private function makeTable(): Table
    {
        return $this->createStub(Table::class);
    }

    public function testConstructorSetsConfig(): void
    {
        $config = new FakeDbConfig();
        $item = new DeclarationItem($config);
        $this->assertSame($config, $item->config);
    }

    public function testGetTablesEmptyInitially(): void
    {
        $item = new DeclarationItem(new FakeDbConfig());
        $this->assertSame([], $item->getTables());
    }

    public function testPushAddsTable(): void
    {
        $item = new DeclarationItem(new FakeDbConfig());
        $table = $this->makeTable();
        $item->push($table);
        $this->assertCount(1, $item->getTables());
        $this->assertSame($table, $item->getTables()[0]);
    }

    public function testPushMultipleTables(): void
    {
        $item = new DeclarationItem(new FakeDbConfig());
        $item->push($this->makeTable());
        $item->push($this->makeTable());
        $item->push($this->makeTable());
        $this->assertCount(3, $item->getTables());
    }
}

// ---------------------------------------------------------------------------
// Declaration tests
// ---------------------------------------------------------------------------

class DeclarationTest extends TestCase
{
    private function makeTable(): Table
    {
        return $this->createStub(Table::class);
    }

    public function testGetItemsEmptyInitially(): void
    {
        $declaration = new Declaration();
        $this->assertSame([], $declaration->getItems());
    }

    public function testPushCreatesNewItem(): void
    {
        $declaration = new Declaration();
        $declaration->push(new FakeDbConfig(), $this->makeTable());
        $this->assertCount(1, $declaration->getItems());
    }

    public function testPushSameConfigClassMergesIntoExistingItem(): void
    {
        $declaration = new Declaration();
        $declaration->push(new FakeDbConfig(), $this->makeTable());
        $declaration->push(new FakeDbConfig(), $this->makeTable());
        // Same config class → single item with two tables
        $this->assertCount(1, $declaration->getItems());
        $this->assertCount(2, $declaration->getItems()[0]->getTables());
    }

    public function testPushDifferentConfigClassesCreatesSeparateItems(): void
    {
        $declaration = new Declaration();
        $declaration->push(new FakeDbConfig(), $this->makeTable());
        $declaration->push(new AnotherFakeDbConfig(), $this->makeTable());
        $this->assertCount(2, $declaration->getItems());
    }

    public function testGetItemsReturnsDeclarationItemInstances(): void
    {
        $declaration = new Declaration();
        $declaration->push(new FakeDbConfig(), $this->makeTable());
        $items = $declaration->getItems();
        $this->assertInstanceOf(DeclarationItem::class, $items[0]);
    }

    public function testItemHoldsCorrectConfig(): void
    {
        $config = new FakeDbConfig();
        $declaration = new Declaration();
        $declaration->push($config, $this->makeTable());
        $this->assertSame($config, $declaration->getItems()[0]->config);
    }

    public function testMultiplePushesPreserveTableOrder(): void
    {
        $declaration = new Declaration();
        $t1 = $this->makeTable();
        $t2 = $this->makeTable();
        $declaration->push(new FakeDbConfig(), $t1);
        $declaration->push(new FakeDbConfig(), $t2);
        $tables = $declaration->getItems()[0]->getTables();
        $this->assertSame($t1, $tables[0]);
        $this->assertSame($t2, $tables[1]);
    }
}
