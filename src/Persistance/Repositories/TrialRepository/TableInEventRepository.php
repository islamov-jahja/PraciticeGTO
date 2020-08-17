<?php


namespace App\Persistance\Repositories\TrialRepository;


use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Domain\Models\Trial\Table;
use App\Domain\Models\Trial\TableInEvent;
use App\Persistance\ModelsEloquant\Trial\TableInEvent AS TableInEventPDO;

class TableInEventRepository implements IRepository
{
    private $dateForCreateTablesInEvent = [
        'table_in_event_id',
        'event_id',
        'id_version_standard',
        'version'
    ];
    public function get(int $id): ?IModel
    {
        $results = TableInEventPDO::query()
            ->join('version_standard', 'version_standard.id_version_standard', '=', 'table_in_event.table_id')
            ->where('table_in_event_id', '=', $id)
            ->get($this->dateForCreateTablesInEvent);

        if (count($results) == 0){
            return null;
        }

        return $this->getTableInEvent($results)[0];
    }

    /**
     * @inheritDoc
     */
    public function getAll(): ?array
    {
        // TODO: Implement getAll() method.
    }

    public function getFilteredByEventId(int $eventId):?IModel
    {
        $results = TableInEventPDO::query()
            ->join('version_standard', 'version_standard.id_version_standard', '=', 'table_in_event.table_id')
            ->where('event_id', '=', $eventId)
            ->get($this->dateForCreateTablesInEvent);

        if (count($results) == 0){
            return null;
        }

        return $this->getTableInEvent($results)[0];
    }

    /**@var $model TableInEvent*/
    public function add(IModel $model): int
    {
        $result = TableInEventPDO::query()
            ->create([
                'table_id' => $model->getTable()->getTableId(),
                'event_id' => $model->getEventId()
            ]);

        return $result->getAttribute('table_in_event_id');
    }

    public function delete(int $id)
    {
        // TODO: Implement delete() method.
    }

    public function update(IModel $model)
    {
        // TODO: Implement update() method.
    }

    private function getTableInEvent($results)
    {
        $tablesInEvent = [];
        foreach ($results as $result){
            $table = new Table($result['id_version_standard'], $result['version']);
            $tablesInEvent[] = new TableInEvent($result['table_in_event_id'], $result['event_id'], $table);
        }

        return $tablesInEvent;
    }
}