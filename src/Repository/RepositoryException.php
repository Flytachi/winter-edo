<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo;

use Flytachi\Winter\Base\Exception\Exception;
use Psr\Log\LogLevel;

class RepositoryException extends Exception
{
    protected string $logLevel = LogLevel::CRITICAL;
}
