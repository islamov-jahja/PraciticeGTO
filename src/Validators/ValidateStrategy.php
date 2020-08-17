<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 06.12.2019
 * Time: 3:36
 */

namespace App\Validators;


interface ValidateStrategy
{
    public function validate(array $params, array $options = null):array ;
}