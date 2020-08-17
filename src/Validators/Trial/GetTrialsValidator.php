<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 06.12.2019
 * Time: 4:30
 */

namespace App\Validators\Trial;


use App\Validators\BaseValidator;

class GetTrialsValidator extends BaseValidator
{
    protected function addSpecificRules(array &$params, array $options = null)
    {
        $params = $this->getInitedResult($params);
        $this->addNotNullNotBlankRules(['gender', 'age']);
        $this->addGreaterThenRule(['age'], 7);
        $this->addInChoiceRule(['gender'], [0, 1]);
    }

    private function getInitedResult(array $params)
    {
        return [
            'gender' => $params['gender'],
            'age' => $params['age']
        ];
    }
}