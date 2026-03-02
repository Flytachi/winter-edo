<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Entity;

use Flytachi\Winter\Cdo\Connection\CDO;
use Flytachi\Winter\Cdo\Qb;

interface RepositoryInterface
{
    public function db(): CDO;
    public function getSchema(): ?string;
    public function originTable(): string;
    public function getEntityClassName(): string;
    public function getDbConfigClassName(): string;
    public function buildSql(): string;
    public function getSql(?string $param = null): mixed;
    public function cleanCache(?string $param = null): void;
    public function select(string $option): static;
    public function as(string $alias): static;
    public function join(string|RepositoryInterface $repository, string $on): static;
    public function joinInner(string|RepositoryInterface $repository, string $on): static;
    public function joinLeft(string|RepositoryInterface $repository, string $on): static;
    public function joinRight(string|RepositoryInterface $repository, string $on): static;
    public function where(?Qb $qb): static;
    public function groupBy(string $context): static;
    public function having(string $context): static;
    public function orderBy(string $context): static;
    public function limit(int $limit, int $offset = 0): static;
    public function forBy(string $context): static;
    public function from(string|RepositoryInterface $repository): static;
    public function with(string $name, RepositoryInterface $repository, ?string $modifier = null): static;
    public function withRecursive(string $name, RepositoryInterface $repository): static;
}
