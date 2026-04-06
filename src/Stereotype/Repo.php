<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Stereotype;

use Flytachi\Winter\Edo\Entity\RepositoryCrudInterface;
use Flytachi\Winter\Edo\Entity\RepositoryViewInterface;
use Flytachi\Winter\Edo\Repository\RepositoryCore;
use Flytachi\Winter\Edo\Repository\RepositoryCrudTrait;
use Flytachi\Winter\Edo\Repository\RepositoryViewTrait;

final class Repo extends RepositoryCore implements RepositoryViewInterface
{
    use RepositoryViewTrait;

    final public function __construct(string $dbConfigClassName)
    {
        $this->dbConfigClassName = $dbConfigClassName;
        parent::__construct();
    }
}
