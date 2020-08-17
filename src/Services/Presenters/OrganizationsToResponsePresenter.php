<?php


namespace App\Services\Presenters;


use App\Domain\Models\Organization;

class OrganizationsToResponsePresenter
{
    /**@var $organizations Organization[]*/
    public static function getView(array $organizations):array
    {
        $organizationsToView = [];

        foreach ($organizations as $organization){
            $organizationsToView[] = $organization->toArray();
        }

        return  $organizationsToView;
    }
}