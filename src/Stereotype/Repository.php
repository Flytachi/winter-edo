<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Stereotype;

use Flytachi\Winter\Edo\Entity\RepositoryCrudInterface;
use Flytachi\Winter\Edo\Entity\RepositoryViewInterface;
use Flytachi\Winter\Edo\Repository\RepositoryCore;
use Flytachi\Winter\Edo\Repository\RepositoryCrudTrait;
use Flytachi\Winter\Edo\Repository\RepositoryViewTrait;

/**
 * Base class for full-access repository implementations (CRUD + View).
 *
 * Combines all read and write operations in a single class. Extend this
 * for the typical repository that needs both SELECT and write access.
 * Define {@see RepositoryCore::$dbConfigClassName}, {@see RepositoryCore::$table},
 * and optionally {@see RepositoryCore::$entityClassName} in the subclass.
 *
 * Example:
 * ```
 * class UserRepository extends Repository
 * {
 *     protected string $dbConfigClassName = DbConfig::class;
 *     protected string $entityClassName   = UserEntity::class;
 *     public static string $table         = 'users';
 * }
 *
 * // Read
 * $user = UserRepository::findById(42);
 *
 * // Write
 * (new UserRepository())->insert($newUser);
 * ```
 *
 * @see RepositoryView  For read-only access
 * @see RepositoryCrud  For write-only access
 */
abstract class Repository extends RepositoryCore implements RepositoryCrudInterface, RepositoryViewInterface
{
    use RepositoryCrudTrait;
    use RepositoryViewTrait;

    final public function __construct()
    {
        parent::__construct();
    }
}
