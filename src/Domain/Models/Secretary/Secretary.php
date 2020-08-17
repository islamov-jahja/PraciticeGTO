<?php

namespace App\Domain\Models\Secretary;
use App\Domain\Models\IModel;
use App\Domain\Models\User\User;

class Secretary implements IModel
{
    private $id;
    private $eventId;
    private $organizationId;
    private $user;

    public function __construct(int $id, int $eventId, int $organizationId, User $user)
    {
        $this->id = $id;
        $this->eventId = $eventId;
        $this->organizationId = $organizationId;
        $this->user = $user;
    }

    public function getId():int
    {
        return $this->id;
    }

    public function getEventId():int
    {
        return $this->eventId;
    }

    public function getOrganizationId():int
    {
        return $this->organizationId;
    }

    public function getUser():User
    {
        return $this->user;
    }

    public function toArray(): array
    {
        $secretary = $this->getUser()->toArray();
        $secretary['secretaryId'] = $this->getId();
        $secretary['eventId'] = $this->getEventId();
        $secretary['organizationId'] = $this->getOrganizationId();

        return  $secretary;
    }
}