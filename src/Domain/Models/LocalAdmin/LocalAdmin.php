<?php

namespace App\Domain\Models\LocalAdmin;
use App\Domain\Models\User\User;

class LocalAdmin implements \App\Domain\Models\IModel
{
    private $id;
    private $user;
    private $organizationId;

    public function __construct(User $user, int $organizationId, int $id)
    {
        $this->user = $user;
        $this->organizationId = $organizationId;
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUser():User
    {
        return $this->user;
    }

    public function getOrganizationId():int
    {
        return $this->organizationId;
    }

    public function toArray(): array
    {
        $localAdmin = $this->user->toArray();
        $localAdmin['organizationId'] = $this->organizationId;
        $localAdmin['localAdminId'] = $this->getId();
        return $localAdmin;
    }
}