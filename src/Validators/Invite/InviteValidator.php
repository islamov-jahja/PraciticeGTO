<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 06.12.2019
 * Time: 3:44
 */

namespace App\Validators\Invite;


use App\Validators\BaseValidator;

class InviteValidator extends BaseValidator
{
    protected function addSpecificRules(array &$params, array $options = null)
    {
        $params = $this->initParams($params);
        $this->addInChoiceRule(['gender'], [0 , 1]);
        $this->addNotNullNotBlankRules(['name', 'email', 'dateOfBirth', 'gender']);
        $this->addMaxLengthRule(['name'], 500);
        $this->addStringRule(['name', 'email']);
        $this->addDateTypeRule(['dateOfBirth']);
        $this->addEmailRule(['email']);
    }

    private function initParams(array $params)
    {
        return [
            'name' => $params['name'] ?? null,
            'email' => $params['email'] ?? null,
            'dateOfBirth' => $params['dateOfBirth'] ?? null,
            'gender' => $params['gender'] ?? null
        ];
    }
}