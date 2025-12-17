<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo;

use Flytachi\Winter\Cdo\Config\Common\DbConfigInterface;
use Flytachi\Winter\Edo\Mapping\Structure\Table;

final class DeclarationItem
{
    private array $tables = [];
    public function __construct(public readonly DbConfigInterface $config)
    {
    }

    public function push(Table $newTable): void
    {
        $this->tables[] = $newTable;
    }

    public function getTables(): array
    {
        return $this->tables;
    }
}
