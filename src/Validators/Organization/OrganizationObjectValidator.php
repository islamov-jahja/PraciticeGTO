<?php
namespace App\Validators\Organization;
use App\Validators\BaseValidator;

class OrganizationObjectValidator extends BaseValidator
{
    protected function addSpecificRules(array &$params, array $options = null)
    {
        $params = $this->getInitedParams($params);
        $this->addNotNullNotBlankRules([
            'id',
            'name',
            'address',
            'leader',
            'phoneNumber',
            'oqrn',
            'paymentAccount',
            'branch',
            'bik',
            'correspondentAccount'
            ]);

        $this->addStringRule(['name', 'address', 'leader', 'phoneNumber', 'paymentAccount', 'branch', 'bik', 'correspondentAccount']);
        $this->addMaxLengthRule(['name', 'address', 'leader', 'phoneNumber', 'paymentAccount', 'branch', 'bik', 'correspondentAccount'], 1000);
        $this->addIntTypeRule(['id']);
    }

    private function getInitedParams(array $params){
        $params['id'] = $params['id'] ?? null;
        $params['name'] = $params['name'] ?? null;
        $params['address'] = $params['address'] ?? null;
        $params['leader'] = $params['leader'] ?? null;
        $params['phoneNumber'] = $params['phoneNumber'] ?? null;
        $params['oqrn'] = $params['oqrn'] ?? null;
        $params['paymentAccount'] = $params['paymentAccount'] ?? null;
        $params['branch'] = $params['branch'] ?? null;
        $params['bik'] = $params['bik'] ?? null;
        $params['correspondentAccount'] = $params['correspondentAccount'] ?? null;

        return $params;
    }
}