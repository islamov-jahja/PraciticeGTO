<?php


namespace App\Domain\Models\User;
use App\Domain\Models\IModelCreater;

class UserCreater implements IModelCreater
{

    public static function createModel(array $rowParams)
    {
        return new User(
            $rowParams['id'],
            $rowParams['name'],
            $rowParams['password'],
            $rowParams['email'],
            $rowParams['roleId'],
            $rowParams['isActivity'],
            $rowParams['dateTime'],
            $rowParams['gender'],
            $rowParams['dateOfBirth']
        );
    }
}