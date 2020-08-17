<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 06.12.2019
 * Time: 4:17
 */

namespace App\Validators\Auth;


use App\Validators\BaseValidator;

class LoginValidator extends BaseValidator
{
    protected function addSpecificRules(array &$params, array $options = null)
    {
        $params = $this->getInitedParams($params);
        $this->addNotNullNotBlankRules(['password', 'email']);
        $this->addEmailRule(['email']);
        $this->addMinLengthRule(['password'], 6);
        $this->addStringRule(['password']);
    }

    private function getInitedParams(array $params)
    {
        return[
            'email'=> $params['email'] ?? null,
            'password' => $params['password'] ?? null
        ];
    }
}