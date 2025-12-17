<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Entity;

use Flytachi\Winter\Cdo\Qb;

interface RepositoryCrudInterface extends RepositoryInterface
{
    public function insert(object|array $model): mixed;
    public function insertGroup(object ...$models): void;
    public function update(object|array $model, Qb $qb): int|string;
    public function delete(Qb $qb): int|string;
}
