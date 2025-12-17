<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Repository;

use Flytachi\Winter\Cdo\Connection\CDOException;
use Flytachi\Winter\Cdo\Qb;
use Flytachi\Winter\Edo\Entity\RepositoryCrudInterface;

/**
 * @mixin RepositoryCrudInterface
 */
trait RepositoryCrudTrait
{
    /**
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
     * @throws RepositoryException
     */
    public function insertGroup(object ...$entities): void
    {
        try {
            $this->db()->insertGroup($this->originTable(), ...$entities);
        } catch (CDOException $exception) {
            throw new RepositoryException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
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
}
