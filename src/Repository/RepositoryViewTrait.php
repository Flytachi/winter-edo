<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Repository;

use Flytachi\Winter\Base\HttpCode;
use Flytachi\Winter\Cdo\Qb;
use Flytachi\Winter\Edo\Entity\EntityException;
use Flytachi\Winter\Edo\Entity\RepositoryViewInterface;
use PDO;

/**
 * @property string $entityClassName
 * @property array $sqlParts sql parameters
 *
 * @mixin RepositoryViewInterface
 */
trait RepositoryViewTrait
{
    /**
     * @param string $sql
     * @param array $binds
     * @param string|null $entityClassName
     * @return array<object>
     * @throws RepositoryException
     */
    final public function rawFetch(string $sql, array $binds = [], ?string $entityClassName = null): array
    {
        try {
            $stmt = $this->db()->prepare($sql);
            foreach ($binds as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_CLASS, $entityClassName ?: $this->entityClassName);
        } catch (\Throwable $th) {
            throw new RepositoryException($th->getMessage(), previous: $th);
        }
    }

    /**
     * @param string|null $entityClassName
     * @return null|object
     * @throws RepositoryException
     */
    final public function find(?string $entityClassName = null): ?object
    {
        try {
            if ($entityClassName) {
                $this->entityClassName = $entityClassName;
            }
            $this->limit(1);
            $stmt = $this->db()->prepare($this->buildSql());
            // Bind
            if (isset($this->sqlParts['binds']) && !empty($this->sqlParts['binds'])) {
                foreach ($this->sqlParts['binds'] as $hash => $value) {
                    $stmt->bindValue($hash, $value);
                }
            }
            $stmt->execute();
            $this->cleanCache();
            return $stmt->fetchObject($entityClassName ?: $this->entityClassName) ?: null;
        } catch (\Throwable $th) {
            throw new RepositoryException($th->getMessage(), previous: $th);
        }
    }

    /**
     * @param int $column column index (started from 0 index)
     * @return mixed
     * @throws RepositoryException
     */
    final public function findColumn(int $column = 0): mixed
    {
        try {
            $this->limit(1);
            $stmt = $this->db()->prepare($this->buildSql());
            // Bind
            if (isset($this->sqlParts['binds']) && !empty($this->sqlParts['binds'])) {
                foreach ($this->sqlParts['binds'] as $hash => $value) {
                    $stmt->bindValue($hash, $value);
                }
            }
            $stmt->execute();
            $this->cleanCache();
            return $stmt->fetchColumn($column);
        } catch (\Throwable $th) {
            throw new RepositoryException($th->getMessage(), previous: $th);
        }
    }

    /**
     * @param string|null $entityClassName
     * @return array<object>
     * @throws RepositoryException
     */
    final public function findAll(?string $entityClassName = null): array
    {
        try {
            if ($entityClassName) {
                $this->entityClassName = $entityClassName;
            }
            $stmt = $this->db()->prepare($this->buildSql());
            // Bind
            if (isset($this->sqlParts['binds']) && !empty($this->sqlParts['binds'])) {
                foreach ($this->sqlParts['binds'] as $hash => $value) {
                    $stmt->bindValue($hash, $value);
                }
            }
            $stmt->execute();
            $this->cleanCache();
            return $stmt->fetchAll(PDO::FETCH_CLASS, $entityClassName ?: $this->entityClassName);
        } catch (\Throwable $th) {
            throw new RepositoryException($th->getMessage(), previous: $th);
        }
    }

    /**
     * @return int
     * @throws RepositoryException
     */
    final public function count(): int
    {
        try {
            $this->sqlParts['option'] = 'COUNT(' . ($this->sqlParts['option'] ?? '*') . ')';
            $stmt = $this->db()->prepare($this->buildSql());
            // Bind
            if (isset($this->sqlParts['binds']) && !empty($this->sqlParts['binds'])) {
                foreach ($this->sqlParts['binds'] as $hash => $value) {
                    $stmt->bindValue($hash, $value);
                }
            }
            $stmt->execute();
            $this->cleanCache();
            return (int) $stmt->fetchColumn(0);
        } catch (\Throwable $th) {
            throw new RepositoryException($th->getMessage(), previous: $th);
        }
    }

    /**
     * @return bool
     * @throws RepositoryException
     */
    final public function exists(): bool
    {
        try {
            $this->sqlParts['option'] = '1';
            $this->limit(1);
            $stmt = $this->db()->prepare($this->buildSql());
            // Bind
            if (isset($this->sqlParts['binds']) && !empty($this->sqlParts['binds'])) {
                foreach ($this->sqlParts['binds'] as $hash => $value) {
                    $stmt->bindValue($hash, $value);
                }
            }
            $stmt->execute();
            $this->cleanCache();
            return (bool) $stmt->fetchColumn(0);
        } catch (\Throwable $th) {
            throw new RepositoryException($th->getMessage(), previous: $th);
        }
    }

    /**
     * Finds a record by its ID.
     *
     * @param int|string $id The ID of the record to find.
     * @param string|null $entityClassName The class name of the entity to use for the find operation. Defaults to null.
     *
     * @return object|null Returns the found record if it exists, or null if it does not.
     * @throws RepositoryException
     */
    final public static function findById(int|string $id, ?string $entityClassName = null): ?object
    {
        $instance = new static();
        return $instance
            ->where(Qb::eq($instance->mapIdentifierColumnName(), $id))
            ->find($entityClassName);
    }

    /**
     * Finds records based on a Qb object.
     *
     * @param Qb $qb The Qb object containing the conditions for the find operation.
     * @param string|null $entityClassName The class name of the entity to use for the find operation. Defaults to null.
     *
     * @return object|null Returns the found records if any exist, or null if none are found.
     * @throws RepositoryException
     */
    final public static function findBy(Qb $qb, ?string $entityClassName = null): ?object
    {
        return (new static())->where($qb)->find($entityClassName);
    }

    /**
     * Finds multiple records based on a set of conditions.
     *
     * @param null|Qb $qb The conditions to use for finding the records. Defaults to null.
     * @param string|null $entityClassName The class name of the entity to use for the find operation. Defaults to null.
     *
     * @return array<object> Returns an array of found records if they exist, or false if no records are found.
     * @throws RepositoryException
     */
    final public static function findAllBy(?Qb $qb = null, ?string $entityClassName = null): array
    {
        return (new static())
            ->where($qb)
            ->findAll($entityClassName);
    }

    /**
     * Finds a record by its ID or throws an error if the record is not found.
     *
     * @param int|string $id The ID of the record to find.
     * @param string|null $entityClassName The class name of the entity to use for the find operation. Defaults to null.
     * @param string $message The error message to be thrown if the record is not found. Defaults to 'Object not found'.
     * @param HttpCode $httpCode The HTTP status code to be used in the error response. Defaults to HttpCode::NOT_FOUND.
     *
     * @return object Returns the found record if it exists.
     * @throws EntityException|RepositoryException
     */
    final public static function findByIdOrThrow(
        int|string $id,
        ?string $entityClassName = null,
        string $message = 'Entity not found',
        HttpCode $httpCode = HttpCode::NOT_FOUND
    ): object {
        $obj = static::findById($id, $entityClassName);
        if (!$obj) {
            throw new EntityException($message, $httpCode->value);
        }
        return $obj;
    }

    /**
     * Finds a record using the provided Qb object and throws an error if the record does not exist.
     *
     * @param Qb $qb The Qb object used to search for the record.
     * @param string|null $entityClassName The class name of the entity to use for the find operation. Defaults to null.
     * @param string $message The error message to throw if the record is not found. Defaults to 'Object not found'.
     * @param HttpCode $httpCode The HTTP status code to be used in the error response. Defaults to HttpCode::NOT_FOUND.
     *
     * @return object Returns the found record if it exists or throws
     * @throws EntityException|RepositoryException
     */
    final public static function findByOrThrow(
        Qb $qb,
        ?string $entityClassName = null,
        string $message = 'Entity not found',
        HttpCode $httpCode = HttpCode::NOT_FOUND
    ): object {
        $obj = static::findBy($qb, $entityClassName);
        if (!$obj) {
            throw new EntityException($message, $httpCode->value);
        }
        return $obj;
    }
}
