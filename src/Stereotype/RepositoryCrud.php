<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Stereotype;

use Flytachi\Winter\Edo\Entity\RepositoryCrudInterface;
use Flytachi\Winter\Edo\Repository\RepositoryCore;
use Flytachi\Winter\Edo\Repository\RepositoryCrudTrait;

abstract class RepositoryCrud extends RepositoryCore implements RepositoryCrudInterface
{
    use RepositoryCrudTrait;
}
