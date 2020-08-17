<?php


namespace App\Domain\Models\Trial;


use App\Domain\Models\IModel;

class TableInEvent implements IModel
{
    private $tableInEventId;
    private $eventId;
    private $table;

    public function __construct(int $tableInEventId, int $eventId, Table $table)
    {
        $this->tableInEventId = $tableInEventId;
        $this->eventId = $eventId;
        $this->table = $table;
    }

    /**
     * @return int
     */
    public function getEventId(): int
    {
        return $this->eventId;
    }

    /**
     * @return Table
     */
    public function getTable(): Table
    {
        return $this->table;
    }

    /**
     * @return int
     */
    public function getTableInEventId(): int
    {
        return $this->tableInEventId;
    }

    public function toArray(): array
    {
        return [
            'tableInEventId' => $this->getTableInEventId(),
            'eventId' => $this->getEventId(),
            'tableId' => $this->getTable()->getTableId(),
            'tableName' => $this->getTable()->getName()
        ];
    }
}