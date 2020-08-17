<?php

namespace App\Domain\Models\TeamLead;

use App\Domain\Models\IModel;
use App\Domain\Models\User\User;

class TeamLead implements IModel
{
    private $teamLeadId;
    private $teamId;
    private $userId;
    private $user;

    public function __construct(int $teamLeadId, $teamId, int $userId, User $user)
    {
        $this->teamLeadId = $teamLeadId;
        $this->teamId = $teamId;
        $this->userId = $userId;
        $this->user = $user;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return mixed
     */
    public function getTeamId()
    {
        return $this->teamId;
    }

    /**
     * @return int
     */
    public function getTeamLeadId(): int
    {
        return $this->teamLeadId;
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
        $user = $this->getUser()->toArray();
        return [
            'teamLeadId' => $this->getTeamLeadId(),
            'teamId' => $this->getTeamId(),
            'userId' => $this->getUserId(),
            'name' => $user['name'],
            'email' => $user['email'],
            'gender' => $user['gender'],
            'dateOfBirth' => $user['dateOfBirth'],
            'isActivity' => $user['isActivity']
        ];
    }
}