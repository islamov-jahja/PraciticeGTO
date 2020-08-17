<?php

namespace App\Domain\Models\User;

use App\Domain\Models\IModel;
use DateTime;
use DateTimeZone;

class User implements IModel
{
    private $id;
    private $name;
    private $password;
    private $email;
    private $roleId;
    private $isActivity;
    private $registrationDate;
    private $dateOfBirth;
    private $gender;
    private $uid;

    public function __construct(
        int $id,
        string $name,
        string $password,
        string $email,
        string $roleId,
        int $isActivity,
        DateTime $registrationDate,
        int $gender,
        DateTime $dateOfBirth
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->password = $password;
        $this->email = $email;
        $this->roleId = $roleId;
        $this->isActivity = $isActivity;
        $this->registrationDate = $registrationDate;
        $this->dateOfBirth = $dateOfBirth;
        if (!in_array($gender, [1, 0])){
            throw new GenderException();
        }

        $this->gender = $gender;
        $this->uid = null;
    }

    public function setUid(string $uid)
    {
        $this->uid = $uid;
    }

    public function getUid():?string
    {
        return $this->uid;
    }

    public function getId():?int
    {
        return $this->id;
    }

    public function getName():string
    {
        return $this->name;
    }

    public function getAge():int
    {
        $date = new DateTime();
        return $date->diff($this->dateOfBirth)->y;
    }

    public function setIsActivity()
    {
        $this->isActivity = 1;
    }

    public function getPassword():string
    {
        return $this->password;
    }

    public function getEmail():string
    {
        return $this->email;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getRoleId():int
    {
        return $this->roleId;
    }

    public function isActivity():int
    {
        return $this->isActivity;
    }

    public function getRegistrationDate():string
    {
        return $this->registrationDate->setTimezone(new DateTimeZone('europe/moscow'))
            ->format('Y-m-d H:i:s');
    }

    public function getDateOfBirth():string
    {
        return $this->dateOfBirth->setTimezone(new DateTimeZone('europe/moscow'))
            ->format('Y-m-d H:i:s');
    }

    public function setRoleId(int $roleId)
    {
        $this->roleId = $roleId;
    }

    public function setId(int $id){
        $this->id = $id;
    }

    public function getGender()
    {
        return $this->gender;
    }

    public function toArray(): array
    {
        return [
            'userId' => $this->getId(),
            'name' => $this->getName(),
            'password' => $this->getPassword(),
            'email' => $this->getEmail(),
            'roleId' => $this->getRoleId(),
            'isActivity' => $this->isActivity(),
            'registrationDate' => $this->getRegistrationDate(),
            'gender' => $this->getGender(),
            'dateOfBirth' => $this->getDateOfBirth(),
            'uid' => $this->getUid()
        ];
    }
}