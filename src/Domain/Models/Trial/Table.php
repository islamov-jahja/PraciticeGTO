<?php

namespace App\Domain\Models\Trial;
use App\Domain\Models\IModel;

class Table implements IModel
{
    private $tableId;
    private $name;
    public function __construct(int $tableId, string $name)
    {
        $this->name = $name;
        $this->tableId = $tableId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getTableId(): int
    {
        return $this->tableId;
    }


    public function toArray(): array
    {
        return [
            'tableId' => $this->getTableId(),
            'name' => $this->getName()
        ];
    }
}