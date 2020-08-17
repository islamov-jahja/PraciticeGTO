<?php

namespace App\Domain\Models\Team;
use App\Domain\Models\IModel;

class Team implements IModel
{
    private $id;
    private $eventId;
    private $name;
    private $countOfPlayers;

    public function __construct(int $id, int $eventId, string $name, int $countOfPlayers = 0)
    {
        $this->id = $id;
        $this->eventId = $eventId;
        $this->name = $name;
        $this->countOfPlayers = $countOfPlayers;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCountOfPlayers():int
    {
        return $this->countOfPlayers;
    }

    public function setCountOfPlayers(int $count)
    {
        $this->countOfPlayers = $count;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'eventId' => $this->getEventId(),
            'name' => $this->getName(),
            'countOfPlayers' => $this->getCountOfPlayers()
        ];
    }
}