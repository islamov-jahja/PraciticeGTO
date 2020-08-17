<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 06.12.2019
 * Time: 3:37
 */

namespace App\Validators;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

abstract class BaseValidator implements ValidateStrategy
{
    protected $validator;
    protected $collectionRules;
    const INVALID_TYPING = 'INVALID_TYPING';
    const EMPTY_TYPING = 'EMPTY_TYPING';
    const GREATER_THEN = 'GREATER_THEN';
    const INVALID_CHOICE = 'INVALID_CHOICE';
    const INVALID_COUNT_IN_ARRAY = 'INVALID_COUNT_IN_ARRAY';
    const NOT_EQUAL = 'NOT_EQUAL';
    const IVALID_EMAIL = 'INVALID_EMAIL';
    const INVALID_LENGTH = 'INVALID_LENGTH';

    public function __construct()
    {
        $this->validator = Validation::createValidator();
        $this->collectionRules = new Assert\Collection([]);
    }

    public function validate(array $params, array $options = null): array
    {
        $this->addBaseRules($params, $options);
        $this->addSpecificRules($params, $options);

        $errors = $this->validator->validate($params, $this->collectionRules);
        return $this->showErrorsInFormatToResponse($errors);
    }

    protected function addBaseRules(array $params, array $options = null)
    {

    }

    protected function addSpecificRules(array &$params, array $options = null)
    {

    }

    protected function addRuleToParam(string $paramName, Constraint $rule)
    {
        if (count($this->collectionRules->fields) == 0){
            $this->collectionRules = new Assert\Collection([$paramName => [$rule]]);
            return;
        }

        if (!isset($this->collectionRules->fields[$paramName])){
            $fields = $this->collectionRules->fields;
            $constraintsObject = new Assert\Required();
            $constraintsObject->constraints[] = $rule;
            $constraintsObject->addImplicitGroupName('Default');

            $fields[$paramName] = $constraintsObject;
            $this->collectionRules = new Assert\Collection($fields);
            return;
        }

        $this->collectionRules->fields[$paramName]->constraints[] = $rule;
    }

    protected function showErrorsInFormatToResponse(ConstraintViolationListInterface $errors):array
    {
        $errorObjects = [];
        foreach ($errors as $error){
            /** @var $error ConstraintViolation*/
            if($error->getMessage() != 'This field was not expected.' && $error->getMessage() != 'This field is missing.'){
                $errorObject = [
                    'description' => explode(':', $error->getMessage())[0],
                    'type' => explode(':', $error->getMessage())[1]
                ];
                $errorObjects[] = $errorObject;
            }
        }

        return $errorObjects;
    }

    protected function addEmailRule(array $paramNames)
    {
        foreach ($paramNames as $paramName) {
            $this->addRuleToParam($paramName, new Assert\Email([
                'message' => '{{' . $paramName . '}} не валидный email :' . BaseValidator::IVALID_EMAIL
            ]));
        }
    }

    protected function addEqualRule(array $paramNames, $equal)
    {
        foreach ($paramNames as $paramName) {
            $this->addRuleToParam($paramName, new Assert\EqualTo([
                'value' => $equal,
                'message' => '{{' . $paramName . '}} должен быть равен ' . $equal . ':' . BaseValidator::NOT_EQUAL
            ]));
        }
    }

    protected function addDateTypeRule(array $paramNames)
    {
        foreach ($paramNames as $paramName) {
            $this->addRuleToParam($paramName, new Assert\Date([
                'message' => '{{' . $paramName . '}} должен быть датой:' . BaseValidator::INVALID_TYPING
            ]));
        }
    }

    protected function addDateTimeTypeRule(array $paramNames)
    {
        foreach ($paramNames as $paramName) {
            $this->addRuleToParam($paramName, new Assert\DateTime([
                'message' => '{{' . $paramName . '}} должен быть датой и временем:' . BaseValidator::INVALID_TYPING
            ]));
        }
    }

    protected function addIntTypeRule(array $paramNames)
    {
        foreach ($paramNames as $paramName) {
            $this->addRuleToParam($paramName, new Assert\Type([
                'type' => 'integer',
                'message' => '{{' . $paramName . '}} должен быть числом:' . BaseValidator::INVALID_TYPING
            ]));
        }
    }

    protected function addNotNullRules(array $paramNames)
    {
        foreach ($paramNames as $paramName) {
            $this->addRuleToParam($paramName, new Assert\NotNull([
                'message' => '{{' . $paramName . '}} не должен быть null:' . BaseValidator::EMPTY_TYPING
            ]));
        }
    }

    protected function addNotNullNotBlankRules(array $paramNames)
    {
        foreach ($paramNames as $paramName) {
            $this->addRuleToParam($paramName, new Assert\NotNull([
                'message' => '{{' . $paramName . '}} не должен быть null:' . BaseValidator::EMPTY_TYPING
            ]));
        }

        foreach ($paramNames as $paramName) {
            $this->addRuleToParam($paramName, new Assert\NotBlank([
                'message' => '{{' . $paramName . '}} не должен быть пустым:' . BaseValidator::EMPTY_TYPING
            ]));
        }
    }

    protected function addGreaterThenRule(array $paramNames, $value)
    {
        foreach ($paramNames as $paramName) {
            $this->addRuleToParam($paramName, new Assert\GreaterThan
            ([
                'value' => $value,
                'message' => '{{' . $paramName . '}} меньше ' . $value . ':' . BaseValidator::GREATER_THEN
            ]));
        }
    }

    protected function addInChoiceRule(array $paramNames, $choices)
    {
        foreach ($paramNames as $paramName) {
            $this->addRuleToParam($paramName, new Assert\Choice([
                'choices' => $choices,
                'message' => '{{' . $paramName . '}} не находится в выборке.:' . self::INVALID_CHOICE,
            ]));
        }
    }

    protected function addMinCountArrayRule(array $paramNames, int $minCount)
    {
        foreach ($paramNames as $paramName) {
            $this->addRuleToParam($paramName, new Assert\Count([
                'min' => $minCount,
                'minMessage' => 'количество элементов в массиве {{' . $paramName . '}}' . ' не должно быть меньше ' . $minCount . ':' . self::INVALID_COUNT_IN_ARRAY
            ]));
        }
    }

    protected function addMinLengthRule(array $paramNames, int $length)
    {
        foreach ($paramNames as $paramName) {
            $this->addRuleToParam($paramName, new Assert\Length([
                'min' => $length,
                'minMessage' => '{{' . $paramName . '}} должен быть длиной не менее ' . $length . ' символов:' . self::INVALID_LENGTH
            ]));
        }
    }

    protected function addMaxLengthRule(array $paramNames, int $length)
    {
        foreach ($paramNames as $paramName) {
            $this->addRuleToParam($paramName, new Assert\Length([
                'max' => $length,
                'minMessage' => '{{' . $paramName . '}} должен быть длиной не более ' . $length . ' символов:' . self::INVALID_LENGTH
            ]));
        }
    }

    protected function addStringRule(array $paramNames)
    {
        foreach ($paramNames as $paramName) {
            $this->addRuleToParam($paramName, new Assert\Type([
                'type' => 'string',
                'message' => '{{' . $paramName . '}} должен быть строкой:' . BaseValidator::EMPTY_TYPING
            ]));
        }
    }
}