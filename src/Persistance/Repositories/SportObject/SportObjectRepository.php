<?php

namespace App\Persistance\Repositories\SportObject;
use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Domain\Models\SportObject\SportObject;
use App\Persistance\ModelsEloquant\SportObject\SportObject AS SportObjectPDO;

class SportObjectRepository implements IRepository
{

    /**@return SportObject*/
    public function get(int $id): ?IModel
    {
        $results = SportObjectPDO::query()
            ->where('sport_object_id', '=', $id)
            ->get();
        if (count($results) == 0){
            return null;
        }

        return $this->getSportObjects($results)[0];
    }

    /**
     * @param int $organizationId
     * @return SportObject[]
     */
    public function getForOrganization(int $organizationId):array
    {
        $results = SportObjectPDO::query()
            ->where('organization_id', '=', $organizationId)
            ->get();

        return $this->getSportObjects($results);
    }
    /**
     * @inheritDoc
     */
    public function getAll(): ?array
    {
        // TODO: Implement getAll() method.
    }

    /**@var $model SportObject*/
    public function add(IModel $model): int
    {
        return SportObjectPDO::query()->create([
            'organization_id' => $model->getOrganizationId(),
            'name' => $model->getName(),
            'address' => $model->getAddress(),
            'description' => $model->getDescription()
        ])->getAttribute('sport_object_id');
    }

    public function delete(int $id)
    {
        SportObjectPDO::query()
            ->where('sport_object_id', '=', $id)
            ->delete();
    }

    /**@var $model SportObject*/
    public function update(IModel $model)
    {
        SportObjectPDO::query()
            ->where('sport_object_id', '=', $model->getSportObjectId())
            ->update([
                'organization_id' => $model->getOrganizationId(),
                'name' => $model->getName(),
                'address' => $model->getAddress(),
                'description' => $model->getDescription()
            ]);
    }

    /**
     * @param array $sportObjectItems
     * @return SportObject[]
     */
    private function getSportObjects($sportObjectItems):array
    {
        $sportObjects = [];

        foreach ($sportObjectItems as $item) {
            $sportObjects[] = new SportObject($item['organization_id'], $item['name'], $item['address'], $item['description'], $item['sport_object_id']);
        }

        return $sportObjects;
    }
}