<?php

namespace App\Validators\SportObject;
use App\Validators\BaseValidator;

class SportObjectValidator extends BaseValidator
{
    protected function addSpecificRules(array &$params, array $options = null)
    {
        $params = $this->getInitedParams($params);
        $this->addNotNullNotBlankRules(['name', 'address']);
        $this->addNotNullRules(['description']);
        $this->addStringRule(['name', 'address', 'description']);
        $this->addMaxLengthRule(['name', 'address', 'description'], 1000);
    }

    private function getInitedParams(array $params)
    {
        return[
            'name' => $params['name'] ?? null,
            'address' => $params['address'] ?? null,
            'description' => $params['description'] ?? null
        ];
    }
}