<?php

namespace App\Validators\User;
use App\Validators\BaseValidator;

class UserValidator extends BaseValidator
{
    protected function addSpecificRules(array &$params, array $options = null)
    {
        $params = $this->getInitedParams($params);
        $this->addNotNullNotBlankRules(['name', 'dateOfBirth', 'gender', 'uid']);
        $this->addStringRule(['name', 'uid']);
        $this->addMaxLengthRule(['name'], 500);
        $this->addDateTypeRule(['date_of_birth']);
        $this->addIntTypeRule(['gender']);
        $this->addInChoiceRule(['gender'], [0, 1]);
    }

    private function getInitedParams($params){
        return [
            'name' => $params['name'] ?? null,
            'dateOfBirth' => $params['dateOfBirth'] ?? null,
            'gender' => $params['gender'] ?? null
        ];
    }
}