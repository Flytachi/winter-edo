<?php

declare(strict_types=1);

namespace Flytachi\Winter\Edo;

use Flytachi\Winter\Cdo\Config\Common\DbConfigInterface;
use Flytachi\Winter\Edo\Mapping\Structure\Table;

final class Declaration
{
    /** @var DeclarationItem[] */
    private array $items;

    public function __construct()
    {
        $this->items = [];
    }

    public function push(DbConfigInterface $config, Table $structureTable): void
    {
        foreach ($this->items as $item) {
            if ($item->config::class === $config::class) {
                $item->push($structureTable);
                return;
            }
        }
        $newItem = new DeclarationItem($config);
        $newItem->push($structureTable);
        $this->items[] = $newItem;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
