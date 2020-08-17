<?php

namespace App\Domain\Models\SportObject;
use App\Domain\Models\IModel;

class SportObject implements IModel
{
    private $sportObjectId;
    private $organizationId;
    private $name;
    private $address;
    private $description;

    public function __construct
    (
        int $organizationId,
        string $name,
        string $address,
        string $description,
        int $sportObjectId
    )
    {
        $this->organizationId = $organizationId;
        $this->name = $name;
        $this->description = $description;
        $this->address = $address;
        $this->sportObjectId = $sportObjectId;
    }

    /**
     * @return int
     */
    public function getOrganizationId(): int
    {
        return $this->organizationId;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
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
    public function getSportObjectId(): int
    {
        return $this->sportObjectId;
    }

    public function toArray(): array
    {
        return [
            'sportObjectId' => $this->getSportObjectId(),
            'organizationId' => $this->getOrganizationId(),
            'name' => $this->getName(),
            'address' => $this->getAddress(),
            'description' => $this->getDescription()
        ];
    }
}