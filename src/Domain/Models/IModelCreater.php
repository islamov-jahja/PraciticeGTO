<?php


namespace App\Domain\Models;


interface IModelCreater
{
    public static function createModel(array $rowParams);
}