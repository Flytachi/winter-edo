<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Repository;

use Flytachi\Winter\Base\Interface\Stereotype;
use Flytachi\Winter\Cdo\Connection\CDO;
use Flytachi\Winter\Cdo\ConnectionPool;
use Flytachi\Winter\Cdo\Qb;
use Flytachi\Winter\Edo\Entity\EntityInterface;
use Flytachi\Winter\Edo\Entity\RepositoryInterface;
use Flytachi\Winter\Edo\Mapping\RepositoryMappingInterface;
use stdClass;

abstract class RepositoryCore extends Stereotype implements RepositoryInterface, RepositoryMappingInterface
{
    /** @var class-string $dbConfigClassName dbConfig class name (default => DbConfig::class) */
    protected string $dbConfigClassName;
    /** @var class-string $entityClassName object class name (default => \stdClass::class) */
    protected string $entityClassName = \stdClass::class;
    /** @var string|null $schema schema in database */
    protected ?string $schema = null;
    /** @var string $table name of the table in the database */
    public static string $table = '';
    /** @var array $sqlParts sql parameters */
    protected array $sqlParts = [];

    public function __construct()
    {
        parent::__construct();
        if (!isset($this->dbConfigClassName)) {
            RepositoryException::throw(static::class . ' $dbConfigClassName must be set by the child class');
        }
        $config = ConnectionPool::getConfigDb($this->dbConfigClassName);
        if ($this->schema == null) {
            $this->schema = $config->getSchema();
        }
    }

    public static function instance(?string $as = null): static
    {
        $repository = new static();
        if (!empty($as)) {
            $repository->as($as);
        }
        return $repository;
    }

    final public function getDbConfigClassName(): string
    {
        return $this->dbConfigClassName;
    }

    final public function getEntityClassName(): string
    {
        return $this->entityClassName;
    }

    /**
     * @return CDO
     */
    public function db(): CDO
    {
        return ConnectionPool::db($this->dbConfigClassName);
    }

    /**
     * @return string|null
     */
    public function getSchema(): ?string
    {
        return $this->schema;
    }

    /**
     * @return string
     */
    public function originTable(): string
    {
        if (empty(static::$table)) {
            return '';
        }
        return (($this->schema) ? $this->schema . '.' : '') . static::$table;
    }

    /**
     * @throws RepositoryException
     */
    public function buildSql(): string
    {
        try {
            $parts = [
                'SELECT ' . $this->prepareSelect(),
                'FROM ' . ($this->sqlParts['from'] ?? $this->originTable())
            ];

            foreach (['as', 'join', 'where', 'group', 'having'] as $key) {
                if (isset($this->sqlParts[$key])) {
                    $parts[] = trim($this->sqlParts[$key]);
                }
            }
            if (isset($this->sqlParts['union'])) {
                $parts[] = $this->sqlParts['union'];
            }
            if (isset($this->sqlParts['order'])) {
                $parts[] = trim($this->sqlParts['order']);
            }
            if (isset($this->sqlParts['limit'])) {
                $parts[] = 'LIMIT ' . $this->sqlParts['limit'];
            }
            if (isset($this->sqlParts['offset'])) {
                $parts[] = 'OFFSET ' . $this->sqlParts['offset'];
            }
            if (isset($this->sqlParts['for'])) {
                $parts[] = 'FOR ' . $this->sqlParts['for'];
            }

            if (isset($this->sqlParts['with'])) {
                $withKeyword = isset($this->sqlParts['with_recursive']) ? 'WITH RECURSIVE' : 'WITH';
                array_unshift($parts, $withKeyword . ' ' . $this->sqlParts['with']);
            }

            $query = implode(' ', $parts);
            $this->logger->debug('Repository build:' . $query);
            return $query;
        } catch (\Throwable $th) {
            throw new RepositoryException($th->getMessage(), previous: $th);
        }
    }

    /**
     * @throws RepositoryException
     */
    final public function getSql(?string $param = null): mixed
    {
        if ($param) {
            return (isset($this->sqlParts[$param])) ? $this->sqlParts[$param] : null;
        } else {
            return $this->buildSql();
        }
    }

    final public function cleanCache(?string $param = null): void
    {
        if ($param) {
            if (isset($this->sqlParts[$param])) {
                unset($this->sqlParts[$param]);
            };
        } else {
            $this->sqlParts = [];
        }
    }

    private function prepareSelect(): string
    {
        if (isset($this->sqlParts['option'])) {
            $this->entityClassName = stdClass::class;
            return $this->sqlParts['option'];
        } elseif ($this->entityClassName === 'stdClass' || is_subclass_of($this->entityClassName, stdClass::class)) {
            return '*';
        } else {
            $prefix = isset($this->sqlParts['as']) ? $this->sqlParts['as'] . '.' : '';
            $values = [];
            $selection = [];
            if (is_subclass_of($this->entityClassName, EntityInterface::class)) {
                $selection = $this->entityClassName::selection();
            }

            foreach (get_class_vars($this->entityClassName) as $name => $val) {
                $values[] = $selection[$name] ?? ($prefix . $name);
            }

            return implode(', ', $values);
        }
    }

    /**
     * @param string $option
     * @return static
     */
    final public function select(string $option): static
    {
        if (!empty($option)) {
            $this->sqlParts['option'] = $option;
        }
        return $this;
    }

    /**
     * @param string $alias
     * @return static
     */
    final public function as(string $alias): static
    {
        if (!empty($alias)) {
            $this->sqlParts['as'] = $alias;
        }
        return $this;
    }

    private function joinedContext(string|RepositoryInterface $repository, string $on): string
    {
        if (is_string($repository)) {
            return $repository . " ON(" . $on . ")";
        }
        if (count($repository->sqlParts) > 1) {
            if (isset($this->sqlParts['binds'])) {
                $this->sqlParts['binds'] = [...$this->sqlParts['binds'], ...$repository->getSql('binds')];
            } else {
                $this->sqlParts['binds'] = $repository->getSql('binds');
            }
            return '(' . $repository->getSql() . ') '
                . $repository->getSql('as') . " ON(" . $on . ")";
        } else {
            return $repository->originTable()
                . ' ' . $repository->getSql('as') . " ON(" . $on . ")";
        }
    }

    /**
     * @param string|RepositoryInterface $repository
     * @return static
     */
    final public function crossJoin(string|RepositoryInterface $repository): static
    {
        if (!is_string($repository)) {
            if (count($repository->sqlParts) > 1) {
                if (isset($this->sqlParts['binds'])) {
                    $this->sqlParts['binds'] = [...$this->sqlParts['binds'], ...$repository->getSql('binds') ?? []];
                } else {
                    $this->sqlParts['binds'] = $repository->getSql('binds') ?? [];
                }
                $repository = '(' . $repository->getSql() . ') ' . $repository->getSql('as');
            } else {
                $repository = $repository->originTable() . ' ' . $repository->getSql('as');
            }
        }
        if (isset($this->sqlParts['join'])) {
            $this->sqlParts['join'] .= ' CROSS JOIN ' . $repository;
        } else {
            $this->sqlParts['join'] = 'CROSS JOIN ' . $repository;
        }
        return $this;
    }

    /**
     * @param string|RepositoryInterface $repository
     * @param string $on
     * @return static
     */
    final public function join(string|RepositoryInterface $repository, string $on): static
    {
        if (isset($this->sqlParts['join'])) {
            $this->sqlParts['join'] .= ' JOIN ' . $this->joinedContext($repository, $on);
        } else {
            $this->sqlParts['join'] = 'JOIN ' . $this->joinedContext($repository, $on);
        }
        return $this;
    }

    /**
     * @param string|RepositoryInterface $repository
     * @param string $on
     * @return static
     */
    final public function joinInner(string|RepositoryInterface $repository, string $on): static
    {
        if (isset($this->sqlParts['join'])) {
            $this->sqlParts['join'] .= ' INNER JOIN ' . $this->joinedContext($repository, $on);
        } else {
            $this->sqlParts['join'] = 'INNER JOIN ' . $this->joinedContext($repository, $on);
        }
        return $this;
    }

    /**
     * @param string|RepositoryInterface $repository
     * @param string $on
     * @return static
     */
    final public function joinLeft(string|RepositoryInterface $repository, string $on): static
    {
        if (isset($this->sqlParts['join'])) {
            $this->sqlParts['join'] .= ' LEFT JOIN ' . $this->joinedContext($repository, $on);
        } else {
            $this->sqlParts['join'] = 'LEFT JOIN ' . $this->joinedContext($repository, $on);
        }
        return $this;
    }

    /**
     * @param string|RepositoryInterface $repository
     * @param string $on
     * @return static
     */
    final public function joinRight(string|RepositoryInterface $repository, string $on): static
    {
        if (isset($this->sqlParts['join'])) {
            $this->sqlParts['join'] .= ' RIGHT JOIN ' . $this->joinedContext($repository, $on);
        } else {
            $this->sqlParts['join'] = 'RIGHT JOIN ' . $this->joinedContext($repository, $on);
        }
        return $this;
    }

    /**
     * @param null|Qb $qb
     * @return static
     */
    final public function where(?Qb $qb): static
    {
        if (!is_null($qb)) {
            if ($qb->getQuery()) {
                $this->sqlParts['where'] = 'WHERE ' . $qb->getQuery();
                if (isset($this->sqlParts['binds']) && !empty($this->sqlParts['binds'])) {
                    $this->sqlParts['binds'] = [...$this->sqlParts['binds'], ...$qb->getCache()];
                } else {
                    $this->sqlParts['binds'] = $qb->getCache();
                }
            }
        }
        return $this;
    }

    final public function andWhere(Qb $qb): static
    {
        return $this->addWhere($qb, 'AND');
    }

    final public function orWhere(Qb $qb): static
    {
        return $this->addWhere($qb, 'OR');
    }

    final public function xorWhere(Qb $qb): static
    {
        return $this->addWhere($qb, 'XOR');
    }

    private function addWhere(Qb $qb, string $operator): static
    {
        if ($qb->getQuery()) {
            if (empty($this->sqlParts['where'])) {
                $this->sqlParts['where'] = 'WHERE ' . $qb->getQuery();
            } else {
                $this->sqlParts['where'] .= " $operator " . $qb->getQuery();
            }

            if (isset($this->sqlParts['binds']) && !empty($this->sqlParts['binds'])) {
                $this->sqlParts['binds'] = [...$this->sqlParts['binds'], ...$qb->getCache()];
            } else {
                $this->sqlParts['binds'] = $qb->getCache();
            }
        }
        return $this;
    }

    /**
     * @param string $context
     * @return static
     */
    final public function groupBy(string $context): static
    {
        if (!empty($context)) {
            $this->sqlParts['group'] = 'GROUP BY ' . $context;
        }
        return $this;
    }

    /**
     * @param string $context
     * @return static
     */
    final public function having(string $context): static
    {
        if (!empty($context)) {
            $this->sqlParts['having'] = 'HAVING ' . $context;
        }
        return $this;
    }

    /**
     * @param string $context
     * @return static
     */
    final public function orderBy(string $context): static
    {
        if (!empty($context)) {
            $this->sqlParts['order'] = 'ORDER BY ' . $context;
        }
        return $this;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return static
     */
    final public function limit(int $limit, int $offset = 0): static
    {
        if ($limit < 1) {
            throw new \TypeError('limit < 1');
        }
        if ($offset < 0) {
            throw new \TypeError('offset < 0');
        }
        $this->sqlParts['limit'] = $limit;
        if ($offset > 0) {
            $this->sqlParts['offset'] = $offset;
        }
        return $this;
    }

    /**
     * @param string $context
     * @return static
     */
    final public function forBy(string $context): static
    {
        $this->sqlParts['for'] = $context;
        return $this;
    }

    /**
     * @param string|RepositoryInterface $repository
     * @return static
     */
    final public function from(string|RepositoryInterface $repository): static
    {
        if (isset($this->sqlParts['from'])) {
            RepositoryException::throw('FROM clause already set: only one FROM source is allowed');
        }
        if (is_string($repository)) {
            $this->sqlParts['from'] = $repository;
        } else {
            if (!isset($this->sqlParts['as'])) {
                RepositoryException::throw('FROM subquery requires an alias: call ->as() before ->from()');
            }
            if (isset($this->sqlParts['binds'])) {
                if (!empty($repository->getSql('binds'))) {
                    $this->sqlParts['binds'] = [...$this->sqlParts['binds'], ...$repository->getSql('binds')];
                }
            } else {
                $this->sqlParts['binds'] = $repository->getSql('binds');
            }
            $this->sqlParts['from'] = '(' . $repository->getSql() . ')';
        }
        return $this;
    }

    /**
     * @param string $name
     * @param RepositoryInterface $repository
     * @param string|null $modifier e.g. 'MATERIALIZED', 'NOT MATERIALIZED'
     * @return static
     */
    final public function with(string $name, RepositoryInterface $repository, ?string $modifier = null): static
    {
        if (isset($this->sqlParts['binds'])) {
            if (!empty($repository->getSql('binds'))) {
                $this->sqlParts['binds'] = [...$this->sqlParts['binds'], ...$repository->getSql('binds')];
            }
        } else {
            $this->sqlParts['binds'] = $repository->getSql('binds');
        }

        $cte = $modifier !== null
            ? $name . ' AS ' . $modifier . ' (' . $repository->buildSql() . ')'
            : $name . ' AS (' . $repository->buildSql() . ')';

        if (isset($this->sqlParts['with'])) {
            $this->sqlParts['with'] .= ', ' . $cte;
        } else {
            $this->sqlParts['with'] = $cte;
        }
        return $this;
    }

    /**
     * @param string $name
     * @param RepositoryInterface $repository
     * @return static
     */
    final public function withRecursive(string $name, RepositoryInterface $repository): static
    {
        $this->sqlParts['with_recursive'] = true;
        return $this->with($name, $repository);
    }

    /**
     * @param RepositoryInterface $repository
     * @return static
     */
    final public function union(RepositoryInterface $repository): static
    {
        return $this->addUnion($repository, 'UNION');
    }

    /**
     * @param RepositoryInterface $repository
     * @return static
     */
    final public function unionAll(RepositoryInterface $repository): static
    {
        return $this->addUnion($repository, 'UNION ALL');
    }

    private function addUnion(RepositoryInterface $repository, string $keyword): static
    {
        if (isset($this->sqlParts['binds'])) {
            if (!empty($repository->getSql('binds'))) {
                $this->sqlParts['binds'] = [...$this->sqlParts['binds'], ...$repository->getSql('binds')];
            }
        } else {
            $this->sqlParts['binds'] = $repository->getSql('binds');
        }

        $unionPart = $keyword . ' ' . $repository->buildSql();

        if (isset($this->sqlParts['union'])) {
            $this->sqlParts['union'] .= ' ' . $unionPart;
        } else {
            $this->sqlParts['union'] = $unionPart;
        }
        return $this;
    }

    public function mapIdentifierColumnName(): string
    {
        return 'id';
    }
}
