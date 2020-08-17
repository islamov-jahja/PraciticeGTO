<?php

namespace App\Persistance\Repositories\Event;
use App\Domain\Models\Event\Event;
use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Persistance\ModelsEloquant\Event\Event as EventPDO;
use DateTime;
use Illuminate\Support\Facades\Date;

class EventRepository implements IRepository
{

    public function get(int $id): ?IModel
    {
        $results = EventPDO::query()->where('event_id', '=', $id)->get();

        if (count($results) == 0){
            return null;
        }

        return new Event(
            $results[0]['event_id'],
            $results[0]['organization_id'],
            $results[0]['name'],
            new DateTime($results[0]['start_date']),
            new DateTime($results[0]['expiration_date']),
            $results[0]['description'],
            $results[0]['status']
        );
    }

    /**
     * @inheritDoc
     */
    public function getAll(): ?array
    {
        $results = EventPDO::query()->get();

        if (count($results) == 0){
            return null;
        }

        $events = [];
        foreach ($results as $result){
            $events[] = [
                'id' => $result['event_id'],
                'organizationId' => $result['organization_id'],
                'name' => $result['name'],
                'startDate' => $result['start_date'],
                'expirationDate' => $result['expiration_date'],
                'description' => $result['description'],
                'status' => $result['status']
            ];
        }

        return $events;
    }

    /**@var $model Event*/
    public function add(IModel $model): int
    {
        $eventInArray = $model->toArray();
        $object = EventPDO::query()->create([
            'organization_id' => $model->getIdOrganization(),
            'name' => $model->getName(),
            'start_date' => $eventInArray['startDate'],
            'expiration_date' => $eventInArray['expirationDate'],
            'description' => $model->getDescription(),
            'status' => $model->getStatus()
        ]);

        return $object->getAttribute('event_id');
    }

    public function delete(int $id)
    {
        EventPDO::query()->where('event_id', '=', $id)->delete();
    }

    /**@var $model Event*/
    public function update(IModel $model)
    {
        $eventInArray = $model->toArray();
        EventPDO::query()->where('event_id', '=', $model->getId())->update([
            'name' => $model->getName(),
            'description' => $model->getDescription(),
            'start_date' => $eventInArray['startDate'],
            'expiration_date' => $eventInArray['expirationDate'],
            'status' => $model->getStatus()
        ]);
    }

    public function getAllFilteredByOrganizationId(int $organizationId)
    {
        $results = EventPDO::query()->where('organization_id', '=', $organizationId)->get();

        if (count($results) == 0){
            return null;
        }

        $events = [];
        foreach ($results as $result){
            $events[] = [
                'id' => $result['event_id'],
                'organizationId' => $result['organization_id'],
                'name' => $result['name'],
                'startDate' => $result['start_date'],
                'expirationDate' => $result['expiration_date'],
                'description' => $result['description'],
                'status' => $result['status']
            ];
        }

        return $events;
    }
}