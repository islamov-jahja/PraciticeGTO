<?php


namespace App\Domain\Models\Referee;


use App\Domain\Models\IModel;
use App\Domain\Models\User\User;

class RefereeOnTrialInEvent implements IModel
{
    private $refereeOnTrialInEventId;
    private $trialInEventId;
    private $user;

    public function __construct(int $refereeOnTrialInEventId, int $trialInEventId, User $user)
    {
        $this->refereeOnTrialInEventId = $refereeOnTrialInEventId;
        $this->trialInEventId = $trialInEventId;
        $this->user = $user;
    }

    /**
     * @return int
     */
    public function getTrialInEventId(): int
    {
        return $this->trialInEventId;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return int
     */
    public function getRefereeOnTrialInEventId(): int
    {
        return $this->refereeOnTrialInEventId;
    }

    public function toArray(): array
    {
        return [
            'refereeOnTrialInEventId' => $this->getRefereeOnTrialInEventId(),
            'trialInEventId' => $this->getTrialInEventId(),
            'userId' => $this->getUser()->getId(),
            'name' => $this->getUser()->getName(),
            'email' => $this->getUser()->getEmail(),
            'dateOfBirth' => $this->getUser()->getDateOfBirth(),
            'gender' => $this->getUser()->getGender()
        ];
    }
}