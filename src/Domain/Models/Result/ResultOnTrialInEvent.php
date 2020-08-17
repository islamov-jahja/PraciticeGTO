<?php

namespace App\Domain\Models\Result;
use App\Domain\Models\IModel;
use App\Domain\Models\Trial\TrialInEvent;
use App\Domain\Models\User\User;

class ResultOnTrialInEvent implements IModel
{
    private $trialInEvent;
    private $user;
    private $resultGuideId;
    private $fistResult;
    private $secondResult;
    private $badge;
    private $resultTrialInEventId;

    public function __construct(TrialInEvent $trialInEvent, User $user, int $resultGuideId, $fistResult, $secondResult, ?string $badge, int $resultTrialInEventId)
    {
        $this->trialInEvent = $trialInEvent;
        $this->user = $user;
        $this->fistResult = $fistResult;
        $this->secondResult = $secondResult;
        $this->badge = $badge;
        $this->resultGuideId = $resultGuideId;
        $this->resultTrialInEventId = $resultTrialInEventId;
        if (!in_array($badge, ['золото', 'серебро', 'бронза']) && $badge != null){
            throw new BadgeException();
        }
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    public function setFirstResult($firstResult)
    {
        $this->fistResult = $firstResult;
    }

    public function setSecondResult($secondResult)
    {
        $this->secondResult = $secondResult;
    }

    public function setBadge(?string $badge)
    {
        $this->badge = $badge;
    }

    /**
     * @return int
     */
    public function getResultTrialInEventId(): int
    {
        return $this->resultTrialInEventId;
    }

    /**
     * @return string
     */
    public function getBadge(): ?string
    {
        return $this->badge;
    }

    /**
     * @return mixed
     */
    public function getFistResult()
    {
        return $this->fistResult;
    }

    /**
     * @return mixed
     */
    public function getSecondResult()
    {
        return $this->secondResult;
    }

    /**
     * @return TrialInEvent
     */
    public function getTrialInEvent(): TrialInEvent
    {
        return $this->trialInEvent;
    }

    /**
     * @return int
     */
    public function getResultGuideId(): int
    {
        return $this->resultGuideId;
    }

    public function toArray(): array
    {

    }
}