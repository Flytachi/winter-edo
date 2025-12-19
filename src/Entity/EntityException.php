<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo\Entity;

use Flytachi\Winter\Base\Exception\Exception;
use Psr\Log\LogLevel;

class EntityException extends Exception
{
    protected string $logLevel = LogLevel::ERROR;
}
