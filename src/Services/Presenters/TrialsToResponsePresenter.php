<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 18.10.2019
 * Time: 0:20
 */

namespace App\Services\Presenters;
use App\Domain\Models\Trial;


class TrialsToResponsePresenter
{
    public static function getView(array $trials, $results = null):array
    {
        /** @var  $trial Trial */
        $responseData = [];
        $itemsOfGroup = [];

        $groupId = -1;
        $tempArray = [];
        $allCount = count($trials);
        $counter = 1;
        foreach ($trials as $trial) {
            if (self::itemInArray($tempArray, $trial)){
                continue;
            }

            if ($trial->getIdGroup() != $groupId && $groupId != -1){
                $responseData[] = $itemsOfGroup;
                $itemsOfGroup = [];
                $groupId = $trial->getIdGroup();
            }

            if ($groupId == -1 || $groupId == $trial->getIdGroup()){
                if($results === null) {
                    $itemsOfGroup['group'][] = self::getTrialVIew($trial);
                }

                if ($results !== null){
                    if (count($results) == 0) {
                        $itemsOfGroup['group'][] = self::getTrialWithNullResults($trial);
                    }
                }

                if ($results !== null){
                    if(count($results) != 0) {
                        $itemsOfGroup['group'][] = self::getTrialWithResults($trial, $results);
                    }
                }

                $itemsOfGroup['necessary'] = $trial->getNecessarily();
                $groupId = $trial->getIdGroup();
            }

            if ($counter == $allCount)
            {
                $responseData[] = $itemsOfGroup;
                $itemsOfGroup = [];
                $groupId = $trial->getIdGroup();
            }
            $counter++;
        }

        return  $responseData;
    }

    /**@var $tempArray Trial[]*/
    private static function itemInArray(array $tempArray, Trial $item)
    {
        foreach ($tempArray as $trial){
            if ($trial->getResultGuideId() == $item->getResultGuideId()){
                return true;
            }
        }

        return false;
    }

    public static function getTrialVIew(Trial $trial):array
    {
        return [
            'trialName' => $trial->getTrialName(),
            'trialId' => $trial->getTrialId(),
            'resultForBronze' => $trial->getResultForBronze(),
            'resultForSilver' => $trial->getResultForSilver(),
            'resultForGold' => $trial->getResultForGold(),
            'typeTime' => $trial->getTypeTime(),
        ];
    }

    private static function getTrialWithNullResults(Trial $trial)
    {
        return [
            'trialName' => $trial->getTrialName(),
            'trialId' => $trial->getTrialId(),
            'resultTrialInEventId' => null,
            'typeTime' => $trial->getTypeTime(),
            'firstResult' => null,
            'secondResult' => null,
            'badge' => null,
            'resultForBronze' => $trial->getResultForBronze(),
            'resultForSilver' => $trial->getResultForSilver(),
            'resultForGold' => $trial->getResultForGold(),
        ];
    }

    private static function getTrialWithResults(Trial $trial, $results)
    {
        return [
            'trialName' => $trial->getTrialName(),
            'trialId' => $trial->getTrialId(),
            'resultTrialOnEventId' => $results[$trial->getTrialId()]['resultTrialInEventId'] ?? null,
            'typeTime' => $trial->getTypeTime(),
            'firstResult' => $results[$trial->getTrialId()]['firstResult'] ?? null,
            'secondResult' => $results[$trial->getTrialId()]['secondResult'] ?? null,
            'badge' => $results[$trial->getTrialId()]['badge'],
            'resultForBronze' => $trial->getResultForBronze(),
            'resultForSilver' => $trial->getResultForSilver(),
            'resultForGold' => $trial->getResultForGold(),
        ];
    }
}