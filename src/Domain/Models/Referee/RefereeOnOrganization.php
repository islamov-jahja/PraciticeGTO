<?php

namespace App\Domain\Models\Referee;
use App\Domain\Models\IModel;
use App\Domain\Models\User\User;

class RefereeOnOrganization implements IModel
{
    private $refereeOnOrganizationId;
    private $organizationId;
    private $userId;
    private $user;

    public function __construct(int $refereeOnOrganizationId, int $organizationId, int $userId, User $user)
    {
        $this->refereeOnOrganizationId = $refereeOnOrganizationId;
        $this->organizationId = $organizationId;
        $this->userId = $userId;
        $this->user = $user;
    }

    /**
     * @return int
     */
    public function getOrganizationId(): int
    {
        return $this->organizationId;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return int
     */
    public function getRefereeOnOrganizationId(): int
    {
        return $this->refereeOnOrganizationId;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    public function toArray(): array
    {
        return [
            'refereeOnOrganizationId' => $this->getRefereeOnOrganizationId(),
            'organizationId' => $this->getOrganizationId(),
            'userId' => $this->getUserId(),
            'name' => $this->getUser()->getName(),
            'email' => $this->getUser()->getEmail(),
            'dateOfBirth' => $this->getUser()->getDateOfBirth(),
            'gender' => $this->getUser()->getGender()
        ];
    }
}