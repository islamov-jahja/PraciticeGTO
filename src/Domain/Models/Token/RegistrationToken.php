<?php

namespace App\Domain\Models\Token;
use App\Domain\Models\IModel;

class RegistrationToken implements IModel
{
    private $registrationTokenId;
    private $token;
    private $dateTimeToDelete;

    public function __construct(int $id, string $token, \DateTime $dateTimeToDelete)
    {
        $this->registrationTokenId = $id;
        $this->token = $token;
        $this->dateTimeToDelete = $dateTimeToDelete;
    }

    /**
     * @return \DateTime
     */
    public function getDateTimeToDelete(): \DateTime
    {
        return $this->dateTimeToDelete;
    }

    /**
     * @return int
     */
    public function getRegistrationTokenId(): int
    {
        return $this->dateTimeToDelete->setTimezone(new \DateTimeZone('europe/moscow'))
        ->format('Y-m-d H:i:s');
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    public function toArray(): array
    {
        return [
            'registrationTokenId' => $this->getRegistrationTokenId(),
            'token' => $this->getToken(),
            'dateTimeToDelete' => $this->getDateTimeToDelete()
        ];
    }
}