<?php

namespace App\Persistance\Repositories\AgeCategory;
use App\Domain\Models\AgeCategory\AgeCategory;
use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Persistance\ModelsEloquant\AgeCategory\AgeCategory as AgeCategoryPDO;
class AgeCategoryRepository implements IRepository
{

    public function get(int $id): ?IModel
    {
        // TODO: Implement get() method.
    }

    /**@return AgeCategory*/
    public function getFilteredByName(string $name)
    {
        $results = AgeCategoryPDO::query()
            ->where('name_age_category', '=', $name)
            ->get();

        if ($results == null){
            return null;
        }

        return $this->getAgeCategories($results)[0];
    }

    private function getAgeCategories($results)
    {
        $ageCategories = [];
        foreach ($results as $result){
            $ageCategories[] = new AgeCategory(
                $result->id_age_category,
                $result->name_age_category,
                $result->min_age,
                $result->max_age,
                $result->m_count_tests_for_bronze,
                $result->m_count_tests_for_silver,
                $result->m_count_tests_for_gold,
                $result->f_count_tests_for_bronze,
                $result->f_count_tests_for_silver,
                $result->f_count_tests_for_gold,
                $result->f_count_tests
            );
        }

        return $ageCategories;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): ?array
    {
        // TODO: Implement getAll() method.
    }

    public function add(IModel $model): int
    {
        // TODO: Implement add() method.
    }

    public function delete(int $id)
    {
        // TODO: Implement delete() method.
    }

    public function update(IModel $model)
    {
        // TODO: Implement update() method.
    }
}