<?php


namespace App\Persistance\Repositories\TrialRepository;


use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Domain\Models\SportObject\SportObject;
use App\Domain\Models\Trial\Trial;
use App\Domain\Models\Trial\TrialInEvent;
use App\Persistance\ModelsEloquant\Trial\TrialInEvent AS TrialInEventPDO;
use DateTime;

class TrialInEventRepository implements IRepository
{
    private $dateForCreateTrialInEvent = [
        'trial_in_event_id',
        'trial_in_event.trial_id',
        'event_id',
        'trial_in_event.sport_object_id',
        'trial.type_time',
        'trial.trial',
        'trial.id_version_standard',
        'sport_object.organization_id',
        'sport_object.name',
        'sport_object.address',
        'sport_object.description',
        'sport_object.sport_object_id'
    ];

    /**@return TrialInEvent*/
    public function get(int $id): ?IModel
    {
        $results = TrialInEventPDO::query()
            ->join('trial', 'trial.id_trial', '=', 'trial_in_event.trial_id')
            ->join('sport_object', 'sport_object.sport_object_id', '=', 'trial_in_event.sport_object_id')
            ->where('trial_in_event_id', '=', $id)
            ->get();

        if (count($results) == null){
            return null;
        }

        return $this->getTrialsInEvent($results)[0];
    }

    public function getFilteredByTrialId(int $trialId, $eventId)
    {
        $results = TrialInEventPDO::query()
            ->join('trial', 'trial.id_trial', '=', 'trial_in_event.trial_id')
            ->join('sport_object', 'sport_object.sport_object_id', '=', 'trial_in_event.sport_object_id')
            ->where('trial_in_event.trial_id', '=', $trialId)
            ->where('event_id', '=', $eventId)
            ->get();

        if (count($results) == null){
            return null;
        }

        return $this->getTrialsInEvent($results)[0];
    }

    /**
     * @param $eventId
     * @return TrialInEvent[]
     */
    public function getFilteredByEventId($eventId):array
    {
        $results = TrialInEventPDO::query()
            ->join('trial', 'trial.id_trial', '=', 'trial_in_event.trial_id')
            ->join('sport_object', 'sport_object.sport_object_id', '=', 'trial_in_event.sport_object_id')
            ->where('event_id', '=', $eventId)
            ->get();

        if (count($results) == null){
            return [];
        }

        return $this->getTrialsInEvent($results);
    }

    private function getTrialsInEvent($results)
    {
        $trialsInEvent = [];
        foreach ($results as $result){
            $trial = new Trial($result['trial_id'], $result['trial'], $result['type_time'], $result['id_version_standard']);
            $sportObject = new SportObject($result['organization_id'], $result['name'], $result['address'], $result['description'], $result['sport_object_id']);
            $trialsInEvent[] = new TrialInEvent($result['trial_in_event_id'], $trial, $result['event_id'], $sportObject, new DateTime($result['start_date_time']));
        }

        return $trialsInEvent;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): ?array
    {
        // TODO: Implement getAll() method.
    }

    /**@var $model TrialInEvent*/
    public function add(IModel $model): int
    {
        $result = TrialInEventPDO::query()
            ->create([
                'trial_id' => $model->getTrial()->getTrialId(),
                'event_id' => $model->getEventId(),
                'sport_object_id' => $model->getSportObject()->getSportObjectId(),
                'start_date_time' => $model->getStartDate()
            ]);

        return $result->getAttribute('trial_in_event_id');
    }

    public function delete(int $id)
    {
        TrialInEventPDO::query()
            ->where('trial_in_event_id', '=', $id)
            ->delete();
    }

    public function update(IModel $model)
    {
        // TODO: Implement update() method.
    }
}