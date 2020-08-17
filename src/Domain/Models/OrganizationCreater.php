<?php


namespace App\Domain\Models;


class OrganizationCreater implements IModelCreater
{

    public static function createModel(array $rowParams): Organization
    {
        return new Organization(
            $rowParams['id'],
            $rowParams['name'],
            $rowParams['address'],
            $rowParams['leader'],
            $rowParams['phoneNumber'],
            $rowParams['oqrn'],
            $rowParams['paymentAccount'],
            $rowParams['branch'],
            $rowParams['bik'],
            $rowParams['correspondentAccount']
        );
    }
}