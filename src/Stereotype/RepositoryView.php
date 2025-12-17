<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Stereotype;

use Flytachi\Winter\Edo\Entity\RepositoryViewInterface;
use Flytachi\Winter\Edo\Repository\RepositoryCore;
use Flytachi\Winter\Edo\Repository\RepositoryViewTrait;

abstract class RepositoryView extends RepositoryCore implements RepositoryViewInterface
{
    use RepositoryViewTrait;
}
