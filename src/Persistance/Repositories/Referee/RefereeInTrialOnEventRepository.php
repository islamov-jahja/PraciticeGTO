<?php


namespace App\Persistance\Repositories\Referee;


use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Domain\Models\Referee\RefereeOnTrialInEvent;
use App\Domain\Models\User\UserCreater;
use App\Persistance\ModelsEloquant\Referee\RefereeOnTrialInEvent AS RefereeOnTrialInEventPDO;
use DateTime;

class RefereeInTrialOnEventRepository implements IRepository
{

    private $dateForCreateReferee = [
        'referee_on_trial_in_event_id',
        'trial_in_event_id',
        'user.user_id',
        'user.password',
        'user.name',
        'user.email',
        'user.role_id',
        'user.is_activity',
        'user.registration_date',
        'user.date_of_birth',
        'user.gender'
    ];
    public function get(int $id): ?IModel
    {
        $results = RefereeOnTrialInEventPDO::query()
            ->join('user', 'user.user_id', '=', 'referee_on_trial_in_event.user_id')
            ->where('referee_on_trial_in_event_id', '=', $id)
            ->get($this->dateForCreateReferee);

        if (count($results) == 0){
            return null;
        }

        return $this->getReferies($results)[0];
    }

    /**
     * @inheritDoc
     */
    public function getAll(): ?array
    {
        // TODO: Implement getAll() method.
    }

    /**@return RefereeOnTrialInEvent[]*/
    public function getFilteredByTrialOnEventId(int $trialOnEventId):array
    {
        $results = RefereeOnTrialInEventPDO::query()
            ->join('user', 'user.user_id', '=', 'referee_on_trial_in_event.user_id')
            ->where('referee_on_trial_in_event.trial_in_event_id', '=', $trialOnEventId)
            ->get($this->dateForCreateReferee);

        if (count($results) == 0){
            return [];
        }

        return $this->getReferies($results);
    }

    private function getReferies($results)
    {
        $referies = [];

        foreach ($results as $result){
            $user = UserCreater::createModel([
                'id' => $result['user_id'],
                'name' => $result['name'],
                'password' => '',
                'email' => $result['email'],
                'roleId' => $result['role_id'],
                'dateTime' => new DateTime($result['registration_date']),
                'isActivity' => $result['is_activity'],
                'dateOfBirth' => new DateTime($result['date_of_birth']),
                'gender' => $result['gender']
            ]);

            $referies[] = new RefereeOnTrialInEvent($result['referee_on_trial_in_event_id'], $result['trial_in_event_id'], $user);
        }

        return $referies;
    }

    /**@var $model RefereeOnTrialInEvent*/
    public function add(IModel $model): int
    {
        $result = RefereeOnTrialInEventPDO::query()
            ->create([
                'trial_in_event_id' => $model->getTrialInEventId(),
                'user_id' => $model->getUser()->getId()
            ]);

        return $result->getAttribute('referee_on_trial_in_event_id');
    }

    public function delete(int $id)
    {
        RefereeOnTrialInEventPDO::query()
            ->where('referee_on_trial_in_event_id', '=', $id)
            ->delete();
    }

    public function update(IModel $model)
    {
        // TODO: Implement update() method.
    }

    public function getFilteredByTrialOnEventIdAndUserId(int $trialInEventId, $userId):?RefereeOnTrialInEvent
    {
        $results = RefereeOnTrialInEventPDO::query()
            ->join('user', 'user.user_id', '=', 'referee_on_trial_in_event.user_id')
            ->where('referee_on_trial_in_event.trial_in_event_id', '=', $trialInEventId)
            ->where('referee_on_trial_in_event.user_id', '=', $userId)
            ->get($this->dateForCreateReferee);

        if (count($results) == 0){
            return null;
        }

        return $this->getReferies($results)[0];
    }
}