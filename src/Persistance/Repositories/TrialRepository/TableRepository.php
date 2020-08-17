<?php


namespace App\Persistance\Repositories\TrialRepository;


use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Domain\Models\Trial\Table;
use App\Persistance\ModelsEloquant\Trial\Table AS TablePDO;

class TableRepository implements IRepository
{

    /**@return Table*/
    public function get(int $id): ?IModel
    {
        $results = TablePDO::query()->where('id_version_standard', '=', $id)->get();
        if (count($results) == 0){
            return null;
        }

        return $this->getTables($results)[0];
    }

    /**
     * @inheritDoc
     */
    public function getAll(): ?array
    {
        $results = TablePDO::query()->get();
        return  $this->getTables($results);
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

    private function getTables($results)
    {
        $tables = [];
        foreach ($results as $result){
            $tables[] = new Table($result['id_version_standard'], $result['version']);
        }

        return $tables;
    }
}