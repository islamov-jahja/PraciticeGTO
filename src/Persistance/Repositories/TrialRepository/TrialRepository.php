<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17.10.2019
 * Time: 23:44
 */

namespace App\Persistance\Repositories\TrialRepository;


use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Domain\Models\Organization;
use App\Domain\Models\Trial;
use App\Persistance\ModelsEloquant\AgeCategory\AgeCategory;
use App\Persistance\ModelsEloquant\ResultGuide\ResultGuide;
use Codeception\PHPUnit\Constraint\Page;
use Illuminate\Database\Capsule\Manager as Capsule;
use Monolog\Logger;
use App\Persistance\ModelsEloquant\Trial\Trial AS TrialPDO;

class TrialRepository implements IRepository
{
    public function getNameOfAgeCategory(int $age)
    {
        $result = AgeCategory::query()
            ->where('min_age', '<=', $age)
            ->where('max_age', '>=', $age)
            ->get();

        return $result[0]->name_age_category;
    }

    public function getList(int $gender, int $age):array
    {
        $ageCategories = AgeCategory::query()
            ->where('min_age', '<=', $age)
            ->where('max_age', '>=', $age)
            ->get();
        if (count($ageCategories) == 0){
            return [];
        }

        $idAgeCategory = $ageCategories[0]->id_age_category;

        $results =  ResultGuide::query()
            ->leftJoin('trial', 'trial.id_trial', '=', 'result_guide.id_trial')
            ->leftJoin('group_result_guide', 'result_guide.id_group_result_guide', '=', 'group_result_guide.id_group_result_guide')
            ->where('result_guide.id_age_category', '=', $idAgeCategory)
            ->where('gender', '=', $gender)
            ->orderBy('result_guide.id_group_result_guide')
            ->get();


        $response = [];
        foreach ($results as $result)
        {
            $silver = str_replace(',', ':', $result->result_for_silver);
            $bronze = str_replace(',', ':', $result->result_for_bronze);
            $gold = str_replace(',', ':', $result->result_for_gold);

            if ($result->type_time == 1){
                $silver = str_replace('.', ':', $result->result_for_silver);
                $bronze = str_replace('.', ':', $result->result_for_bronze);
                $gold = str_replace('.', ':', $result->result_for_gold);
            }

            //добавить фильтрацию для времени в минутах и секундах
            $response[] = new Trial($result->trial, $result->id_result_guide, $result->id_trial, $silver, $bronze,
                $gold, 0, $result->necessarily, $result->id_group_result_guide, $result->type_time);
        }

        return $response;
    }

    public function getSecondResult($firstResult, int $allDataStandardId):int
    {
        $translatorModels = ResultGuide::query()
            ->leftJoin('trial', 'trial.id_trial', 'result_guide.id_trial')
            ->where('id_result_guide', '=', $allDataStandardId)
            ->get();

        $typeTime = $translatorModels[0]->type_time;
        $values = $translatorModels[0]->results;
        $values = explode(';', $values);

        return (int)$this->getTranslatedResult($values, $firstResult, $typeTime);
    }

    private function isCorrectResult($firstResult, $dataInBase)
    {
        $firstResultArray = explode(':',$firstResult);
        $firstResultMinutes = $firstResultArray[0];
        $firstResultSeconds = $firstResultArray[1];
        $firstResultMilSeconds = $firstResultArray[2];

        $dataInBaseArray = explode(':', $dataInBase);
        $dataInBaseMinutes = $dataInBaseArray[0];
        $dataInBaseSeconds = $dataInBaseArray[1];
        $dataInBaseMilSeconds = $dataInBaseArray[2];

        if ($dataInBaseMinutes >= $firstResultMinutes){
            if ($dataInBaseSeconds >= $firstResultSeconds){
                if ($dataInBaseMilSeconds >= $firstResultMilSeconds){
                    return true;
                }
            }
        }

        return false;
    }

    private function getTranslatedResult(array $results, string $firstResult, int $typeTime):int
    {
        $firstResult = str_replace('.', ',', $firstResult);
        for($i = 0; $i < count($results) - 1; $i++){
           $keyValue = explode('=', $results[$i]);
           $keyValue[1] = $this->getInTimeFormat($typeTime, $keyValue[1]);
           if ($typeTime != 1) {
               if ($keyValue[1] <= $firstResult) {
                   return (int)$keyValue[0];
               }
           }

           if ($typeTime == 1){
               if ($this->isCorrectResult($firstResult, $keyValue[1])) {
                   return (int)$keyValue[0];
               }
           }
        }

        return 0;
    }

    private function getInTimeFormat(int $typeTime, string $result)
    {
        $resultChange = $result;
        if ($typeTime == 1 && strpos($result, ':') !== false){
            return str_replace('.', ':', $result);
        }

        if ($typeTime == 1 && (strpos($result, ',') !== false || strpos($result, '.') !== false)){
            $resultChange = explode(',', $result);

            if (count($resultChange) == 1){
                $resultChange = explode('.', $result);
            }

            $seconds = (int)$resultChange[0];
            $milSeconds = (int)$resultChange[1];
            $resultString = '00:';

            if ($seconds <= 9){
                $resultString = $resultString.'0'.$seconds.':';
            }else{
                $resultString = $resultString.$seconds.':';
            }

            if ($milSeconds <= 9){
                $resultString = $resultString.$milSeconds.'0';
            }

            return $resultString;
        }

        return $resultChange;
    }

    /**@return Trial\Trial*/
    public function get(int $id): IModel
    {
        $results = TrialPDO::query()
            ->where('id_trial', '=', $id)
            ->get();

        return $this->getTrials($results)[0];
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        // TODO: Implement getAll() method.
    }

    public function getFilteredByTableId(int $tableId)
    {
        $results = TrialPDO::query()
            ->where('id_version_standard', '=', $tableId)
            ->get();

        return $this->getTrials($results);
    }

    private function getTrials($results)
    {
        $trials = [];
        foreach ($results as $result){
            $trials[] = new Trial\Trial($result['id_trial'], $result['trial'], $result['type_time'], $result['id_version_standard']);
        }

        return $trials;
    }

    public function add(IModel $model):int
    {
        // TODO: Implement add() method.
    }

    public function delete(int $id)
    {
        // TODO: Implement delete() method.
    }

    public function update(IModel $organization)
    {
        // TODO: Implement update() method.
    }

    public function getAgeCategoriesForTrialId($trialId)
    {
        $results = ResultGuide::query()
            ->join('age_category', 'age_category.id_age_category', '=', 'result_guide.id_age_category')
            ->where('id_trial', '=', $trialId)
            ->get(['age_category.name_age_category']);

        if ($results == null){
            return null;
        }

        $response = [];
        foreach ($results as $result){
            if (!in_array($result['name_age_category'], $response)){
                $response[] = $result['name_age_category'];
            }
        }

        return $response;
    }
}