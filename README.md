# Winter EDO

[![Latest Version on Packagist](https://img.shields.io/packagist/v/flytachi/winter-edo.svg)](https://packagist.org/packages/flytachi/winter-edo)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)

**EDO** (Entity of Database Objects) — the repository layer for the Winter framework.
Wraps [Winter CDO](https://packagist.org/packages/flytachi/winter-cdo) with a fluent
SQL query builder, entity hydration, and ready-made CRUD and read-only stereotypes.

**Full documentation:** https://winterframe.net/docs/edo

---

## Requirements

- PHP >= 8.3
- ext-pdo
- flytachi/winter-base ^1.1
- flytachi/winter-cdo ^2.0
- flytachi/winter-edo-mapping ^1.0

## Installation

```bash
composer require flytachi/winter-edo
```

---

## Quick Start

### 1. Define an entity

```php
use Flytachi\Winter\Edo\Entity\EntityInterface;

class UserEntity implements EntityInterface
{
    public int    $id;
    public string $name;
    public string $email;
    public string $status;

    public static function selection(): array
    {
        return [];  // use plain property names
    }
}
```

### 2. Define a repository

```php
use Flytachi\Winter\Edo\Stereotype\Repository;

class UserRepository extends Repository
{
    protected string $dbConfigClassName = DbConfig::class;
    protected string $entityClassName   = UserEntity::class;
    public static string $table         = 'users';
}
```

### 3. Read data

```php
use Flytachi\Winter\Cdo\Qb;

// By primary key:
$user = UserRepository::findById(42);            // UserEntity|null

// With a condition:
$user = UserRepository::findBy(Qb::eq('email', 'alice@example.com'));

// All matching:
$users = UserRepository::findAllBy(Qb::eq('status', 'active'));

// Fluent query builder:
$users = UserRepository::instance('u')
    ->joinLeft('orders o', 'u.id = o.user_id')
    ->where(Qb::eq('u.status', 'active'))
    ->orderBy('u.name ASC')
    ->limit(20)
    ->findAll();

// Throw if not found:
$user = UserRepository::findByIdOrThrow(42, message: 'User not found');
```

### 4. Write data

```php
$repo = new UserRepository();

// Insert:
$id = $repo->insert(['name' => 'Alice', 'email' => 'alice@example.com', 'status' => 'active']);

// Update:
$repo->update(['status' => 'inactive'], Qb::eq('id', $id));

// Delete:
$repo->delete(Qb::eq('id', $id));

// Upsert:
$repo->upsert(
    ['email' => 'alice@example.com', 'name' => 'Alice Updated'],
    ['email'],
    ['name' => ':new']
);
```

---

## Stereotypes

| Class | Use when |
|-------|---------|
| `Repository` | Need both reads and writes (most common) |
| `RepositoryView` | Read-only (views, reports, projections) |
| `RepositoryCrud` | Write-only (import pipelines, event sinks) |
| `CteRepo` | Ad-hoc query without a dedicated class |

```php
// Ad-hoc:
use Flytachi\Winter\Edo\Stereotype\CteRepo;

$rows = (new CteRepo(DbConfig::class))
    ->from('audit_log al')
    ->joinLeft('users u', 'al.user_id = u.id')
    ->where(Qb::gt('al.created_at', '2024-01-01'))
    ->select('al.action, u.name, al.created_at')
    ->orderBy('al.created_at DESC')
    ->limit(100)
    ->findAll();
```

---

## Query Builder — Method Reference

### Clause order

```
WITH [RECURSIVE] → SELECT → FROM → AS → JOIN → WHERE → GROUP BY → HAVING
→ UNION → ORDER BY → LIMIT / OFFSET → FOR
```

| Method | SQL clause | Notes |
|--------|-----------|-------|
| `with($name, $repo, $modifier)` | `WITH … AS (…)` | CTE; chain for multiple |
| `withRecursive($name, $repo)` | `WITH RECURSIVE … AS (…)` | Recursive CTE |
| `select($expr)` | `SELECT $expr` | Overrides auto column list |
| `from($source)` | `FROM …` | String or repository subquery |
| `as($alias)` | table alias | Also sets column prefix |
| `join($repo, $on)` | `JOIN … ON(…)` | |
| `joinInner($repo, $on)` | `INNER JOIN … ON(…)` | |
| `joinLeft($repo, $on)` | `LEFT JOIN … ON(…)` | |
| `joinRight($repo, $on)` | `RIGHT JOIN … ON(…)` | |
| `joinCross($repo)` | `CROSS JOIN …` | No ON condition |
| `where($qb)` | `WHERE …` | Accepts `null` (no-op) |
| `andWhere($qb)` | `… AND …` | Acts as `where()` if empty |
| `orWhere($qb)` | `… OR …` | Acts as `where()` if empty |
| `xorWhere($qb)` | `… XOR …` | Acts as `where()` if empty |
| `groupBy($expr)` | `GROUP BY …` | |
| `having($expr)` | `HAVING …` | |
| `union($repo)` | `UNION …` | |
| `unionAll($repo)` | `UNION ALL …` | |
| `orderBy($expr)` | `ORDER BY …` | |
| `limit($n, $offset)` | `LIMIT … OFFSET …` | OFFSET omitted when 0 |
| `forBy($expr)` | `FOR …` | Locking clause |
| `binding($binds)` | — | Manual bind injection |

---

## Documentation

Full documentation is in the [`docs/`](docs/) directory:

| File | Contents |
|------|----------|
| [00-overview.md](docs/00-overview.md) | Architecture, quick start, doc index |
| [01-stereotypes.md](docs/01-stereotypes.md) | Choosing the right base class |
| [02-configuration.md](docs/02-configuration.md) | Properties, EntityInterface |
| [03-select-from.md](docs/03-select-from.md) | `select()`, `from()`, `as()` |
| [04-joins.md](docs/04-joins.md) | All JOIN types, subquery joins |
| [05-where.md](docs/05-where.md) | WHERE conditions |
| [06-group-having.md](docs/06-group-having.md) | `groupBy()`, `having()` |
| [07-union.md](docs/07-union.md) | `union()`, `unionAll()` |
| [08-order-limit.md](docs/08-order-limit.md) | `orderBy()`, `limit()`, `forBy()` |
| [09-with-cte.md](docs/09-with-cte.md) | CTEs, recursive queries |
| [10-binds.md](docs/10-binds.md) | Manual bind injection |
| [11-sql-management.md](docs/11-sql-management.md) | `buildSql()`, `getSql()`, `cleanCache()` |
| [12-view-fetch.md](docs/12-view-fetch.md) | `find`, `findAll`, `count`, `exists`, `rawFetch` |
| [13-static-finders.md](docs/13-static-finders.md) | Static finders and `*OrThrow` variants |
| [14-crud.md](docs/14-crud.md) | `insert`, `update`, `delete`, `upsert` |
| [15-declaration.md](docs/15-declaration.md) | Schema structure registry |
| [16-advanced-examples.md](docs/16-advanced-examples.md) | Real-world examples |

---

## License

MIT — see [LICENSE](LICENSE).
