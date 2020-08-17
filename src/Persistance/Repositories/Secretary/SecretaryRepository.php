<?php

namespace App\Persistance\Repositories\Secretary;
use App\Domain\Models\Event\Event;
use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Domain\Models\Secretary\Secretary;
use App\Domain\Models\User\UserCreater;
use App\Persistance\ModelsEloquant\Secretary\Secretary as SecretaryPDO;
use DateTime;

class SecretaryRepository implements IRepository
{
    private $dateForCreateSecretary = [
        'event.organization_id',
        'secretary.secretary_id',
        'secretary.event_id',
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

    public function get(int $id):?IModel
    {
        $results = SecretaryPDO::query()->join('event', 'event.event_id', '=', 'secretary.event_id')
            ->join('user', 'user.user_id', '=', 'secretary.user_id')
            ->where('secretary.secretary_id', '=', $id)
            ->get($this->dateForCreateSecretary);

        if (count($results) == 0){
            return null;
        }

        $user = UserCreater::createModel([
            'id' => $results[0]['user_id'],
            'name' => $results[0]['name'],
            'password' => '',
            'email' => $results[0]['email'],
            'roleId' => $results[0]['role_id'],
            'dateTime' => new DateTime($results[0]['registration_date']),
            'isActivity' => $results[0]['is_activity'],
            'dateOfBirth' => new DateTime($results[0]['date_of_birth']),
            'gender' => $results[0]['gender']
        ]);

        return new Secretary($results[0]['secretary_id'], $results[0]['event_id'], $results[0]['organization_id'], $user);
    }

    /**
     * @param int $eventId
     * @return Secretary[]
     */
    public function getFilteredByEventId(int $eventId): ?array
    {
        $results = SecretaryPDO::query()->join('event', 'event.event_id', '=', 'secretary.event_id')
            ->join('user', 'user.user_id', '=', 'secretary.user_id')
            ->where('secretary.event_id', '=', $eventId)
            ->get($this->dateForCreateSecretary);

        if (count($results) == 0){
            return null;
        }

        return $this->getSecretaries($results);
    }


    public function getFilteredByOrgId(int $organizationId): ?array
    {
        $results = SecretaryPDO::query()->join('event', 'event.event_id', '=', 'secretary.event_id')
            ->join('user', 'user.user_id', '=', 'secretary.user_id')
            ->where('event.organization_id', '=', $organizationId)
            ->get($this->dateForCreateSecretary);

        return $this->getSecretaries($results);
    }


    public function getAll(): ?array
    {

    }

    /**@return int
     * @var $model Secretary
     */
    public function add(IModel $model): int
    {
        return SecretaryPDO::query()->create([
            'user_id' => $model->getUser()->getId(),
            'event_id' => $model->getEventId()
        ])->getAttribute('secretary_id');
    }

    public function delete(int $id)
    {
        SecretaryPDO::query()->where([
            'secretary_id' => $id
        ])->delete();
    }

    public function update(IModel $model)
    {
        // TODO: Implement update() method.
    }

    public function getFilteredByUserEmail(string $userEmail):?array
    {
        $results = SecretaryPDO::query()
            ->join('event', 'event.event_id', '=', 'secretary.event_id')
            ->join('user', 'user.user_id', '=', 'secretary.user_id')
            ->where('user.email',  '=', $userEmail)
            ->get($this->dateForCreateSecretary);

        if (count($results) == 0){
            return null;
        }

        return $this->getSecretaries($results);
    }

    private function getSecretaries($results)
    {
        $secretaries = [];

        foreach ($results as $result) {
            $user = UserCreater::createModel([
                'id' => $result['user_id'],
                'name' => $result['name'],
                'password' => $result['password'],
                'email' => $result['email'],
                'roleId' => $result['role_id'],
                'dateTime' => new DateTime($result['registration_date']),
                'isActivity' => $result['is_activity'],
                'dateOfBirth' => new DateTime($result['date_of_birth']),
                'gender' => $result['gender']
            ]);

            $secretaries[] = new Secretary($result['secretary_id'], $result['event_id'], $result['organization_id'], $user);
        }

        return $secretaries;
    }
}