<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Entity;

use Flytachi\Winter\Base\HttpCode;
use Flytachi\Winter\Cdo\Qb;

interface RepositoryViewInterface extends RepositoryInterface
{
    public function rawFetch(string $sql, array $binds = [], ?string $entityClassName = null): array;
    public function find(?string $entityClassName = null): ?object;
    public function findColumn(int $column = 0): mixed;
    public function findAll(?string $entityClassName = null): array;
    public function count(): int;
    public function exists(): bool;
    public static function findById(int|string $id, ?string $entityClassName = null): ?object;
    public static function findBy(Qb $qb, ?string $entityClassName = null): ?object;
    public static function findAllBy(?Qb $qb = null, ?string $entityClassName = null): array;
    public static function findByIdOrThrow(
        int|string $id,
        ?string $entityClassName = null,
        string $message = 'Entity not found',
        HttpCode $httpCode = HttpCode::NOT_FOUND
    ): object;
    public static function findByOrThrow(
        Qb $qb,
        ?string $entityClassName = null,
        string $message = 'Entity not found',
        HttpCode $httpCode = HttpCode::NOT_FOUND
    ): object;
}
