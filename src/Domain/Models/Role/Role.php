<?php

namespace App\Domain\Models\Role;
class Role implements \App\Domain\Models\IModel
{
    private $roleId;
    private $nameOfRole;

    public function __construct(int $roleId, string $nameOfRole)
    {
        $this->roleId = $roleId;
        $this->nameOfRole = $nameOfRole;
    }

    public function getId():int
    {
        return $this->roleId;
    }

    public function getName():string
    {
        return $this->nameOfRole;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'nameOfRole' => $this->getName()
        ];
    }
}