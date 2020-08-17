<?php


namespace App\Validators\Auth;


use App\Validators\BaseValidator;

class EmailValidator extends BaseValidator
{
    protected function addSpecificRules(array &$params, array $options = null)
    {
        $params = $this->getInitedParams($params);

        $this->addNotNullNotBlankRules(['email']);
        $this->addEmailRule(['email']);
    }

    private function getInitedParams($params){
        return [
            'email' => $params['email']
        ];
    }
}