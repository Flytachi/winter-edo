<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Tests\Unit;

use Flytachi\Winter\Cdo\Qb;
use Flytachi\Winter\Edo\Repository\RepositoryCore;
use Flytachi\Winter\Edo\Repository\RepositoryException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

// ---------------------------------------------------------------------------
// Test stubs
// ---------------------------------------------------------------------------

/**
 * Concrete stub that bypasses ConnectionPool for unit tests.
 * Exposes $sqlParts via public property for assertion helpers.
 */
class ConcreteRepositoryStub extends RepositoryCore
{
    protected string $dbConfigClassName = 'DummyDbConfig';
    public static string $table = 'users';

    public function __construct(?string $schema = null)
    {
        $this->setLogger(new NullLogger());
        $this->schema = $schema;
    }
}

/**
 * Stub with no table defined — for testing empty-table behaviour.
 */
class EmptyTableRepositoryStub extends RepositoryCore
{
    protected string $dbConfigClassName = 'DummyDbConfig';
    public static string $table = '';

    public function __construct()
    {
        $this->setLogger(new NullLogger());
    }
}

/**
 * Stub with a custom identifier column name.
 */
class CustomIdRepositoryStub extends RepositoryCore
{
    protected string $dbConfigClassName = 'DummyDbConfig';
    public static string $table = 'products';

    public function __construct()
    {
        $this->setLogger(new NullLogger());
    }

    public function mapIdentifierColumnName(): string
    {
        return 'product_id';
    }
}

// ---------------------------------------------------------------------------
// Test case
// ---------------------------------------------------------------------------

class RepositoryCoreTest extends TestCase
{
    private function repo(?string $schema = null): ConcreteRepositoryStub
    {
        return new ConcreteRepositoryStub($schema);
    }

    // -----------------------------------------------------------------------
    // Getters
    // -----------------------------------------------------------------------

    public function testGetDbConfigClassName(): void
    {
        $this->assertSame('DummyDbConfig', $this->repo()->getDbConfigClassName());
    }

    public function testGetEntityClassName(): void
    {
        $this->assertSame(\stdClass::class, $this->repo()->getEntityClassName());
    }

    public function testGetSchemaNull(): void
    {
        $this->assertNull($this->repo()->getSchema());
    }

    public function testGetSchemaValue(): void
    {
        $this->assertSame('myschema', $this->repo('myschema')->getSchema());
    }

    // -----------------------------------------------------------------------
    // originTable
    // -----------------------------------------------------------------------

    public function testOriginTableNoSchema(): void
    {
        $this->assertSame('users', $this->repo()->originTable());
    }

    public function testOriginTableWithSchema(): void
    {
        $this->assertSame('public.users', $this->repo('public')->originTable());
    }

    public function testOriginTableEmptyTable(): void
    {
        $this->assertSame('', (new EmptyTableRepositoryStub())->originTable());
    }

    // -----------------------------------------------------------------------
    // buildSql — basic
    // -----------------------------------------------------------------------

    public function testBuildSqlDefault(): void
    {
        $this->assertSame('SELECT * FROM users', $this->repo()->buildSql());
    }

    public function testBuildSqlWithSchema(): void
    {
        $this->assertSame('SELECT * FROM public.users', $this->repo('public')->buildSql());
    }

    public function testBuildSqlNoTableNoFrom(): void
    {
        $sql = (new EmptyTableRepositoryStub())->buildSql();
        $this->assertSame('SELECT *', $sql);
    }

    // -----------------------------------------------------------------------
    // select
    // -----------------------------------------------------------------------

    public function testSelect(): void
    {
        $sql = $this->repo()->select('id, name')->buildSql();
        $this->assertStringContainsString('SELECT id, name', $sql);
        $this->assertStringContainsString('FROM users', $sql);
    }

    public function testSelectIgnoredWhenEmpty(): void
    {
        $sql = $this->repo()->select('')->buildSql();
        $this->assertStringContainsString('SELECT *', $sql);
    }

    // -----------------------------------------------------------------------
    // from
    // -----------------------------------------------------------------------

    public function testFromString(): void
    {
        $sql = $this->repo()->from('custom_table')->buildSql();
        $this->assertStringContainsString('FROM custom_table', $sql);
    }

    public function testFromStringOverridesOriginTable(): void
    {
        $sql = $this->repo()->from('other')->buildSql();
        $this->assertStringNotContainsString('users', $sql);
    }

    public function testFromDuplicateThrows(): void
    {
        $this->expectException(RepositoryException::class);
        $this->repo()->from('t1')->from('t2');
    }

    public function testFromSubqueryWithoutAliasThrows(): void
    {
        $this->expectException(RepositoryException::class);
        $sub = $this->repo();
        $this->repo()->from($sub);
    }

    public function testFromSubqueryWithAlias(): void
    {
        $sub = $this->repo();
        $repo = $this->repo();
        $repo->as('sq')->from($sub);
        $sql = $repo->buildSql();
        $this->assertStringContainsString('FROM (SELECT * FROM users)', $sql);
        $this->assertStringContainsString('sq', $sql);
    }

    // -----------------------------------------------------------------------
    // as
    // -----------------------------------------------------------------------

    public function testAs(): void
    {
        $sql = $this->repo()->as('u')->buildSql();
        $this->assertStringContainsString('FROM users', $sql);
        $this->assertStringContainsString(' u', $sql);
    }

    public function testAsIgnoredWhenEmpty(): void
    {
        $repo = $this->repo()->as('');
        $this->assertNull($repo->getSql('as'));
    }

    // -----------------------------------------------------------------------
    // JOIN — string form
    // -----------------------------------------------------------------------

    public function testJoin(): void
    {
        $sql = $this->repo()->join('orders o', 'u.id = o.user_id')->buildSql();
        $this->assertStringContainsString('JOIN orders o ON(u.id = o.user_id)', $sql);
    }

    public function testJoinCross(): void
    {
        $sql = $this->repo()->joinCross('other_table')->buildSql();
        $this->assertStringContainsString('CROSS JOIN other_table', $sql);
    }

    public function testJoinInner(): void
    {
        $sql = $this->repo()->joinInner('orders o', 'u.id = o.user_id')->buildSql();
        $this->assertStringContainsString('INNER JOIN orders o ON(u.id = o.user_id)', $sql);
    }

    public function testJoinLeft(): void
    {
        $sql = $this->repo()->joinLeft('orders o', 'u.id = o.user_id')->buildSql();
        $this->assertStringContainsString('LEFT JOIN orders o ON(u.id = o.user_id)', $sql);
    }

    public function testJoinRight(): void
    {
        $sql = $this->repo()->joinRight('orders o', 'u.id = o.user_id')->buildSql();
        $this->assertStringContainsString('RIGHT JOIN orders o ON(u.id = o.user_id)', $sql);
    }

    public function testMultipleJoinsConcatenate(): void
    {
        $sql = $this->repo()
            ->joinLeft('orders o', 'u.id = o.user_id')
            ->joinLeft('payments p', 'o.id = p.order_id')
            ->buildSql();
        $this->assertStringContainsString('LEFT JOIN orders o ON(u.id = o.user_id)', $sql);
        $this->assertStringContainsString('LEFT JOIN payments p ON(o.id = p.order_id)', $sql);
    }

    // -----------------------------------------------------------------------
    // JOIN — repository form
    // -----------------------------------------------------------------------

    public function testJoinCrossRepository(): void
    {
        $other = $this->repo()->as('o');
        $sql = $this->repo()->joinCross($other)->buildSql();
        $this->assertStringContainsString('CROSS JOIN users', $sql);
    }

    public function testJoinRepositorySubquery(): void
    {
        $other = $this->repo()->as('o')->where(Qb::eq('status', 'active'));
        $sql = $this->repo()->join($other, 'u.id = o.id')->buildSql();
        $this->assertStringContainsString('JOIN (SELECT * FROM users', $sql);
        $this->assertStringContainsString('ON(u.id = o.id)', $sql);
    }

    // -----------------------------------------------------------------------
    // WHERE
    // -----------------------------------------------------------------------

    public function testWhereAddsClause(): void
    {
        $sql = $this->repo()->where(Qb::eq('id', 1))->buildSql();
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringContainsString('id', $sql);
    }

    public function testWhereNullNoOp(): void
    {
        $sql = $this->repo()->where(null)->buildSql();
        $this->assertStringNotContainsString('WHERE', $sql);
    }

    public function testAndWhereAppendsAnd(): void
    {
        $sql = $this->repo()
            ->where(Qb::eq('id', 1))
            ->andWhere(Qb::eq('status', 'active'))
            ->buildSql();
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringContainsString(' AND ', $sql);
    }

    public function testAndWhereWithoutWhereActsAsWhere(): void
    {
        $sql = $this->repo()->andWhere(Qb::eq('id', 1))->buildSql();
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringNotContainsString(' AND ', $sql);
    }

    public function testOrWhereAppendsOr(): void
    {
        $sql = $this->repo()
            ->where(Qb::eq('id', 1))
            ->orWhere(Qb::eq('id', 2))
            ->buildSql();
        $this->assertStringContainsString(' OR ', $sql);
    }

    public function testXorWhereAppendsXor(): void
    {
        $sql = $this->repo()
            ->where(Qb::eq('id', 1))
            ->xorWhere(Qb::eq('id', 2))
            ->buildSql();
        $this->assertStringContainsString(' XOR ', $sql);
    }

    public function testWhereBindsAreStored(): void
    {
        $repo = $this->repo()->where(Qb::eq('id', 42));
        $binds = $repo->getSql('binds');
        $this->assertIsArray($binds);
        $this->assertCount(1, $binds);
        $this->assertInstanceOf(\Flytachi\Winter\Cdo\CDOBind::class, $binds[0]);
        $this->assertSame(42, $binds[0]->getValue());
    }

    // -----------------------------------------------------------------------
    // GROUP BY / HAVING
    // -----------------------------------------------------------------------

    public function testGroupBy(): void
    {
        $sql = $this->repo()->groupBy('status')->buildSql();
        $this->assertStringContainsString('GROUP BY status', $sql);
    }

    public function testGroupByIgnoredWhenEmpty(): void
    {
        $sql = $this->repo()->groupBy('')->buildSql();
        $this->assertStringNotContainsString('GROUP BY', $sql);
    }

    public function testHaving(): void
    {
        $sql = $this->repo()->having('COUNT(*) > 1')->buildSql();
        $this->assertStringContainsString('HAVING COUNT(*) > 1', $sql);
    }

    public function testHavingIgnoredWhenEmpty(): void
    {
        $sql = $this->repo()->having('')->buildSql();
        $this->assertStringNotContainsString('HAVING', $sql);
    }

    // -----------------------------------------------------------------------
    // UNION
    // -----------------------------------------------------------------------

    public function testUnion(): void
    {
        $other = $this->repo();
        $sql = $this->repo()->union($other)->buildSql();
        $this->assertStringContainsString('UNION SELECT * FROM users', $sql);
    }

    public function testUnionAll(): void
    {
        $other = $this->repo();
        $sql = $this->repo()->unionAll($other)->buildSql();
        $this->assertStringContainsString('UNION ALL SELECT * FROM users', $sql);
    }

    public function testMultipleUnions(): void
    {
        $sql = $this->repo()
            ->union($this->repo())
            ->union($this->repo())
            ->buildSql();
        $this->assertSame(2, substr_count($sql, 'UNION SELECT'));
    }

    // -----------------------------------------------------------------------
    // ORDER BY
    // -----------------------------------------------------------------------

    public function testOrderBy(): void
    {
        $sql = $this->repo()->orderBy('id DESC')->buildSql();
        $this->assertStringContainsString('ORDER BY id DESC', $sql);
    }

    public function testOrderByIgnoredWhenEmpty(): void
    {
        $sql = $this->repo()->orderBy('')->buildSql();
        $this->assertStringNotContainsString('ORDER BY', $sql);
    }

    // -----------------------------------------------------------------------
    // LIMIT / OFFSET
    // -----------------------------------------------------------------------

    public function testLimit(): void
    {
        $sql = $this->repo()->limit(10)->buildSql();
        $this->assertStringContainsString('LIMIT 10', $sql);
        $this->assertStringNotContainsString('OFFSET', $sql);
    }

    public function testLimitWithOffset(): void
    {
        $sql = $this->repo()->limit(10, 20)->buildSql();
        $this->assertStringContainsString('LIMIT 10', $sql);
        $this->assertStringContainsString('OFFSET 20', $sql);
    }

    public function testLimitZeroOffsetNotAdded(): void
    {
        $sql = $this->repo()->limit(5, 0)->buildSql();
        $this->assertStringNotContainsString('OFFSET', $sql);
    }

    public function testLimitZeroThrows(): void
    {
        $this->expectException(\TypeError::class);
        $this->repo()->limit(0);
    }

    public function testLimitNegativeThrows(): void
    {
        $this->expectException(\TypeError::class);
        $this->repo()->limit(-1);
    }

    public function testNegativeOffsetThrows(): void
    {
        $this->expectException(\TypeError::class);
        $this->repo()->limit(10, -1);
    }

    // -----------------------------------------------------------------------
    // FOR
    // -----------------------------------------------------------------------

    public function testForBy(): void
    {
        $sql = $this->repo()->forBy('UPDATE')->buildSql();
        $this->assertStringContainsString('FOR UPDATE', $sql);
    }

    // -----------------------------------------------------------------------
    // WITH / WITH RECURSIVE
    // -----------------------------------------------------------------------

    public function testWith(): void
    {
        $cte = $this->repo();
        $sql = $this->repo()->with('cte', $cte)->buildSql();
        $this->assertStringStartsWith('WITH cte AS (', $sql);
    }

    public function testWithMaterializedModifier(): void
    {
        $cte = $this->repo();
        $sql = $this->repo()->with('cte', $cte, 'MATERIALIZED')->buildSql();
        $this->assertStringContainsString('WITH cte AS MATERIALIZED (', $sql);
    }

    public function testMultipleCtes(): void
    {
        $sql = $this->repo()
            ->with('cte1', $this->repo())
            ->with('cte2', $this->repo())
            ->buildSql();
        $this->assertStringContainsString('WITH cte1 AS (', $sql);
        $this->assertStringContainsString(', cte2 AS (', $sql);
    }

    public function testWithRecursive(): void
    {
        $cte = $this->repo();
        $sql = $this->repo()->withRecursive('cte', $cte)->buildSql();
        $this->assertStringStartsWith('WITH RECURSIVE cte AS (', $sql);
    }

    // -----------------------------------------------------------------------
    // getSql / cleanCache
    // -----------------------------------------------------------------------

    public function testGetSqlNullReturnsFullSql(): void
    {
        $this->assertSame('SELECT * FROM users', $this->repo()->getSql());
    }

    public function testGetSqlWithParam(): void
    {
        $repo = $this->repo()->orderBy('id');
        $this->assertSame('ORDER BY id', $repo->getSql('order'));
    }

    public function testGetSqlMissingParamReturnsNull(): void
    {
        $this->assertNull($this->repo()->getSql('nonexistent'));
    }

    public function testCleanCacheAll(): void
    {
        $repo = $this->repo()->orderBy('id')->groupBy('status');
        $repo->cleanCache();
        $this->assertNull($repo->getSql('order'));
        $this->assertNull($repo->getSql('group'));
    }

    public function testCleanCacheSpecificKey(): void
    {
        $repo = $this->repo()->orderBy('id')->groupBy('status');
        $repo->cleanCache('order');
        $this->assertNull($repo->getSql('order'));
        $this->assertSame('GROUP BY status', $repo->getSql('group'));
    }

    public function testCleanCacheNonexistentKeyNoOp(): void
    {
        $repo = $this->repo();
        $repo->cleanCache('nonexistent'); // should not throw
        $this->assertSame('SELECT * FROM users', $repo->buildSql());
    }

    // -----------------------------------------------------------------------
    // mapIdentifierColumnName
    // -----------------------------------------------------------------------

    public function testMapIdentifierColumnNameDefault(): void
    {
        $this->assertSame('id', $this->repo()->mapIdentifierColumnName());
    }

    public function testMapIdentifierColumnNameCustom(): void
    {
        $this->assertSame('product_id', (new CustomIdRepositoryStub())->mapIdentifierColumnName());
    }

    // -----------------------------------------------------------------------
    // SQL clause order in buildSql
    // -----------------------------------------------------------------------

    public function testSqlClauseOrder(): void
    {
        $sql = $this->repo('public')
            ->as('u')
            ->joinLeft('orders o', 'u.id = o.user_id')
            ->where(Qb::eq('u.status', 'active'))
            ->groupBy('u.status')
            ->having('COUNT(*) > 0')
            ->orderBy('u.id DESC')
            ->limit(10, 5)
            ->forBy('UPDATE')
            ->buildSql();

        $fromPos    = strpos($sql, 'FROM');
        $aliasPos   = strpos($sql, ' u ');
        $joinPos    = strpos($sql, 'LEFT JOIN');
        $wherePos   = strpos($sql, 'WHERE');
        $groupPos   = strpos($sql, 'GROUP BY');
        $havingPos  = strpos($sql, 'HAVING');
        $orderPos   = strpos($sql, 'ORDER BY');
        $limitPos   = strpos($sql, 'LIMIT');
        $offsetPos  = strpos($sql, 'OFFSET');
        $forPos     = strpos($sql, 'FOR');

        $this->assertGreaterThan($fromPos, $aliasPos);
        $this->assertGreaterThan($aliasPos, $joinPos);
        $this->assertGreaterThan($joinPos, $wherePos);
        $this->assertGreaterThan($wherePos, $groupPos);
        $this->assertGreaterThan($groupPos, $havingPos);
        $this->assertGreaterThan($havingPos, $orderPos);
        $this->assertGreaterThan($orderPos, $limitPos);
        $this->assertGreaterThan($limitPos, $offsetPos);
        $this->assertGreaterThan($offsetPos, $forPos);
    }
}
