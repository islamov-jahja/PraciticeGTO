<?php

namespace App\Persistance\Repositories\EventParticipant;
use App\Domain\Models\EventParticipant\EventParticipant;
use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Domain\Models\User\UserCreater;
use App\Persistance\ModelsEloquant\EventParticipant\EventParticipant as EventParticipantPDO;
use DateTime;


class EventParticipantRepository implements IRepository
{

    private $dateForParticipant = [
        'user.password',
        'user.user_id',
        'user.name',
        'user.email',
        'user.role_id',
        'user.is_activity',
        'user.registration_date',
        'event_participant.event_participant_id',
        'event_participant.team_id',
        'event_participant.event_id',
        'event_participant.confirmed',
        'user.gender',
        'user.date_of_birth'];

    public function get(int $id): ?IModel
    {
        $result = EventParticipantPDO::query()
            ->join('user', 'event_participant.user_id', '=', 'user.user_id')
            ->where('event_participant_id', '=', $id)
            ->get($this->dateForParticipant);

        if (count($result) == 0){
            return null;
        }

        return $this->getEventParticipant($result[0]);
    }

    /**
     * @inheritDoc
     */
    public function getAll(): ?array
    {
        // TODO: Implement getAll() method.
    }

    /**@var $model EventParticipant*/
    public function add(IModel $model): int
    {
        return EventParticipantPDO::query()->create([
            'event_id' => $model->getEventId(),
            'team_id' => $model->getTeamId(),
            'confirmed' => $model->isConfirmed(),
            'user_id' => $model->getUserId()
        ])->getAttribute('event_participant_id');
    }

    public function delete(int $id)
    {
        EventParticipantPDO::query()
            ->where('event_participant_id', '=', $id)
            ->delete();
    }

    /**@var $model EventParticipant*/
    public function update(IModel $model)
    {
        EventParticipantPDO::query()->where('event_participant_id', '=', $model->getEventParticipantId())->update([
            'team_id' => $model->getTeamId(),
            'event_id' => $model->getEventId(),
            'confirmed' => $model->isConfirmed(),
            'user_id' => $model->getUserId()
        ]);
    }

    public function getByEmailAndEvent(string $email, $eventId):?EventParticipant
    {
        $result = EventParticipantPDO::query()
                ->join('user', 'event_participant.user_id', '=', 'user.user_id')
                ->where([
                    'user.email' => $email,
                    'event_id' => $eventId
                ])
                ->get($this->dateForParticipant);

        if (count($result) == 0){
            return null;
        }

        return $this->getEventParticipant($result[0]);
    }

    /**@return EventParticipant[]*/
    public function getByEmail(string $email):array
    {
        $results = EventParticipantPDO::query()
            ->join('user', 'event_participant.user_id', '=', 'user.user_id')
            ->where([
                'user.email' => $email
            ])
            ->get($this->dateForParticipant);

        $participants = [];
        foreach ($results as $result){
            $participants[] = $this->getEventParticipant($result);
        }

        return $participants;
    }

    private function getEventParticipant($params):EventParticipant
    {
        $user = UserCreater::createModel([
            'id' => $params['user_id'],
            'name' => $params['name'],
            'password' => $params['password'],
            'email' => $params['email'],
            'roleId' => $params['role_id'],
            'dateTime' => new DateTime($params['registration_date']),
            'isActivity' => $params['is_activity'],
            'dateOfBirth' => new DateTime($params['date_of_birth']),
            'gender' => $params['gender']
        ]);

        return new EventParticipant
        (
            $params['event_participant_id'],
            $params['event_id'],
            $params['user_id'],
            $params['confirmed'],
            $user,
            $params['team_id']
        );
    }

    public function getAllByEventId($eventId)
    {
        $results = EventParticipantPDO::query()
            ->join('user', 'event_participant.user_id', '=', 'user.user_id')
            ->where('event_id', '=', $eventId)->get($this->dateForParticipant);
        $response = [];
        foreach ($results as $result){
            $response[] = $this->getEventParticipant($result);
        }

        return $response;
    }

    /**
     * @param int $teamId
     * @return EventParticipant[]
     */
    public function getAllFilteredByTeamId(int $teamId):array
    {
        $results = EventParticipantPDO::query()
            ->join('user', 'event_participant.user_id', '=', 'user.user_id')
            ->where('team_id', '=', $teamId)->get($this->dateForParticipant);
        $response = [];
        foreach ($results as $result){
            $response[] = $this->getEventParticipant($result);
        }

        return $response;
    }
}