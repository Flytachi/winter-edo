<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Repository;

use Flytachi\Winter\Cdo\Connection\CDO;
use Flytachi\Winter\Cdo\Connection\CDOException;
use Flytachi\Winter\Cdo\Qb;
use Flytachi\Winter\Edo\Entity\RepositoryCrudInterface;

/**
 * @mixin RepositoryCrudInterface
 */
trait RepositoryCrudTrait
{
    /**
     * @see CDO::insert()
     * @throws RepositoryException
     */
    public function insert(object|array $entity): mixed
    {
        try {
            return $this->db()->insert($this->originTable(), $entity);
        } catch (CDOException $exception) {
            throw new RepositoryException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @see CDO::insertGroup()
     * @throws RepositoryException
     */
    public function insertGroup(array|object ...$entities): void
    {
        try {
            $this->db()->insertGroup($this->originTable(), $entities);
        } catch (CDOException $exception) {
            throw new RepositoryException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @see CDO::update()
     * @throws RepositoryException
     */
    public function update(object|array $entity, Qb $qb): int|string
    {
        try {
            return $this->db()->update($this->originTable(), $entity, $qb);
        } catch (CDOException $exception) {
            throw new RepositoryException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @see CDO::delete()
     * @throws RepositoryException
     */
    public function delete(Qb $qb): int|string
    {
        try {
            return $this->db()->delete($this->originTable(), $qb);
        } catch (CDOException $exception) {
            throw new RepositoryException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @see CDO::upsert()
     * @throws RepositoryException
     */
    public function upsert(
        object|array $entity,
        array $conflictColumns,
        ?array $updateColumns = null
    ): mixed {
        try {
            return $this->db()->upsert($this->originTable(), $entity, $conflictColumns, $updateColumns);
        } catch (CDOException $exception) {
            throw new RepositoryException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @see CDO::upsertGroup()
     * @throws RepositoryException
     */
    public function upsertGroup(
        array $entities,
        array $conflictColumns,
        ?array $updateColumns = null
    ): void {
        try {
            $this->db()->upsertGroup($this->originTable(), $entities, $conflictColumns, $updateColumns);
        } catch (CDOException $exception) {
            throw new RepositoryException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
