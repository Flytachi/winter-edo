<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Entity;

use Flytachi\Winter\Cdo\Qb;

interface RepositoryCrudInterface extends RepositoryInterface
{
    public function insert(object|array $entity): mixed;
    public function insertGroup(object ...$entities): void;
    public function update(object|array $entity, Qb $qb): int|string;
    public function delete(Qb $qb): int|string;
}
