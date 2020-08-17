<?php


namespace App\Domain\Models\Trial;


use App\Domain\Models\IModel;

class Trial implements IModel
{
    private $trialId;
    private $name;
    private $typeTime;
    private $tableId;

    public function __construct(int $trialId, string $name, bool $typeTime, int $tableId)
    {
        $this->trialId = $trialId;
        $this->name = $name;
        $this->typeTime = $typeTime;
        $this->tableId = $tableId;
    }

    /**
     * @return int
     */
    public function getTableId(): int
    {
        return $this->tableId;
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
    public function getTrialId(): int
    {
        return $this->trialId;
    }

    /**
     * @return bool
     */
    public function isTypeTime(): bool
    {
        return $this->typeTime;
    }

    public function toArray(): array
    {
        return [
            'trialId' => $this->getTrialId(),
            'name' => $this->getName(),
            'isTypeTime' => $this->isTypeTime(),
            'tableId' => $this->getTableId()
        ];
    }
}