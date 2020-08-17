<?php

namespace App\Domain\Models\EventParticipant;

use App\Domain\Models\IModel;
use App\Domain\Models\User\User;

class EventParticipant implements IModel
{
    private $eventParticipantId;
    private $eventId;
    private $userId;
    private $teamId;
    private $confirmed;
    private $user;

    public function __construct(int $eventParticipantId, int $eventId,  int $userId, bool $confirmed, User $user, ?int $teamId = null)
    {
        $this->eventParticipantId = $eventParticipantId;
        $this->eventId = $eventId;
        $this->userId = $userId;
        $this->teamId = $teamId;
        $this->confirmed = $confirmed;
        $this->user = $user;
        $ar = $user->toArray();
    }

    /**
     * @return int
     */
    public function getEventId(): int
    {
        return $this->eventId;
    }

    /**
     * @return int
     */
    public function getEventParticipantId(): int
    {
        return $this->eventParticipantId;
    }

    /**
     * @return int
     */
    public function getTeamId(): ?int
    {
        return $this->teamId;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return bool
     */
    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function getUser():User
    {
        return $this->user;
    }

    public function toArray(): array
    {
        $user = $this->getUser()->toArray();
        return [
            'EventParticipantId' => $this->getEventParticipantId(),
            'userId' => $this->getUserId(),
            'eventId' => $this->getEventId(),
            'teamId' => $this->getTeamId(),
            'isConfirmed' => $this->isConfirmed(),
            'name' => $user['name'],
            'email' => $user['email'],
            'gender' => $user['gender'],
            'dateOfBirth' => $user['dateOfBirth'],
            'isActivity' => $user['isActivity']
        ];
    }

    public function doConfirm()
    {
        $this->confirmed = true;
    }
}