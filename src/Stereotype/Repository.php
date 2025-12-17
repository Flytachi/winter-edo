<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Stereotype;

use Flytachi\Winter\Edo\Entity\RepositoryCrudInterface;
use Flytachi\Winter\Edo\Entity\RepositoryViewInterface;
use Flytachi\Winter\Edo\Repository\RepositoryCore;
use Flytachi\Winter\Edo\Repository\RepositoryCrudTrait;
use Flytachi\Winter\Edo\Repository\RepositoryViewTrait;

abstract class Repository extends RepositoryCore implements RepositoryCrudInterface, RepositoryViewInterface
{
    use RepositoryCrudTrait;
    use RepositoryViewTrait;
}
