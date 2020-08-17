<?php


namespace App\Domain\Models\Secretary;


use App\Domain\Models\IModel;
use App\Domain\Models\User\User;

class SecretaryOnOrganization implements IModel
{
    private $secretary_on_organization_id;
    private $userId;
    private $user;
    private $organizationId;

    public function __construct(int $secretary_on_organization_id, int $userId, int $organizationId, User $user)
    {
        $this->user = $user;
        $this->organizationId = $organizationId;
        $this->userId = $userId;
        $this->secretary_on_organization_id = $secretary_on_organization_id;
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
    public function getUserId(): int
    {
        return $this->userId;
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
    public function getSecretaryOnOrganizationId(): int
    {
        return $this->secretary_on_organization_id;
    }

    public function toArray(): array
    {
        return [
            'secretaryOnOrganizationId' => $this->getSecretaryOnOrganizationId(),
            'organizationId' => $this->getOrganizationId(),
            'userId' => $this->getUserId(),
            'name' => $this->getUser()->getName(),
            'gender' => $this->getUser()->getGender(),
            'dateOfBirth' => $this->getUser()->getDateOfBirth(),
            'email' => $this->getUser()->getEmail()
        ];
    }
}