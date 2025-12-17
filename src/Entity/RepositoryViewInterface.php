<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Entity;

use Flytachi\Winter\Cdo\Qb;

interface RepositoryViewInterface extends RepositoryInterface
{
    public function find(?string $modelClassName = null): mixed;
    public function findColumn(int $column = 0): mixed;
    public function findAll(?string $modelClassName = null): ?array;
    public static function findById(int|string $id, ?string $modelClassName = null): mixed;
    public static function findBy(Qb $qb, ?string $modelClassName = null): mixed;
    public static function findAllBy(?Qb $qb = null, ?string $modelClassName = null): array|false;
    public static function findByIdOrThrow(
        int|string $id,
        ?string $modelClassName = null,
        string $message = 'Object not found'
    ): mixed;
    public static function findByOrThrow(
        Qb $qb,
        ?string $modelClassName = null,
        string $message = 'Object not found'
    ): mixed;
}
