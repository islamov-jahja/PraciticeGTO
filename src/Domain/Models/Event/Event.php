<?php

namespace App\Domain\Models\Event;
use App\Domain\Models\IModel;
use DateTime;

class Event implements IModel
{
    public const LEAD_UP = 'подготовка';
    public const PREPARADNESS = 'готовность';
    public const HOLDING = 'проведение';
    public const COMPLETED = 'завершен';

    private $id;
    private $idOrganization;
    private $name;
    private $startDate;
    private $expirationDate;
    private $descrition;
    private $status;


    public function __construct(int $id, int $idOrganization, string $name, DateTime $startDate, DateTime $expirationDate, $descrition = '', string $status = self::LEAD_UP)
    {
        $this->id = $id;
        $this->idOrganization = $idOrganization;
        $this->name = $name;
        $this->startDate = $startDate;
        $this->expirationDate = $expirationDate;
        $this->descrition = $descrition;
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status){
        $this->status = $status;
    }

    public function getId():int
    {
        return $this->id;
    }

    public function getIdOrganization():int
    {
        return $this->idOrganization;
    }

    public function getName():string
    {
        return $this->name;
    }

    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    public function getExpirationDate(): DateTime
    {
        return $this->expirationDate;
    }

    public function getDescription():string
    {
        return $this->descrition;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'idOrganization' => $this->getIdOrganization(),
            'name' => $this->getName(),
            'startDate' => $this->getStartDate()->format('Y-m-d H:i:s'),
            'expirationDate' => $this->getExpirationDate()->format('Y-m-d H:i:s'),
            'description' => $this->getDescription(),
            'status' => $this->getStatus()
        ];
    }
}