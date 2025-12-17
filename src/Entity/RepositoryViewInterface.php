<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Entity;

use Flytachi\Winter\Cdo\Qb;

interface RepositoryViewInterface extends RepositoryInterface
{
    public function find(?string $entityClassName = null): mixed;
    public function findColumn(int $column = 0): mixed;
    public function findAll(?string $entityClassName = null): ?array;
    public static function findById(int|string $id, ?string $entityClassName = null): mixed;
    public static function findBy(Qb $qb, ?string $entityClassName = null): mixed;
    public static function findAllBy(?Qb $qb = null, ?string $entityClassName = null): array|false;
    public static function findByIdOrThrow(
        int|string $id,
        ?string $entityClassName = null,
        string $message = 'Entity not found'
    ): mixed;
    public static function findByOrThrow(
        Qb $qb,
        ?string $entityClassName = null,
        string $message = 'Entity not found'
    ): mixed;
}
