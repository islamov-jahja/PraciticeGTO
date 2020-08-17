<?php

namespace App\Persistance\Repositories\Referee;
use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Domain\Models\Referee\RefereeOnOrganization as RefereeOnOrganizationModel;
use App\Domain\Models\User\UserCreater;
use App\Persistance\ModelsEloquant\Referee\RefereeOnOrganization;
use App\Persistance\ModelsEloquant\Referee\RefereeOnOrganization AS RefereePDO;
use DateTime;

class RefereeRepository implements IRepository
{
    private $dateForCreateReferee = [
        'referee_on_organization.referee_on_organization_id',
        'referee_on_organization.organization_id',
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
        $results = RefereeOnOrganization::query()
            ->join('user', 'user.user_id', '=', 'referee_on_organization.user_id')
            ->where('referee_on_organization_id', '=', $id)
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

    public function getFilteredByUserEmail(string $userEmail):?array
    {
        $results = RefereeOnOrganization::query()
            ->join('user', 'user.user_id', '=', 'referee_on_organization.user_id')
            ->where('user.email',  '=', $userEmail)
            ->get($this->dateForCreateReferee);

        if (count($results) == 0){
            return null;
        }

        return $this->getReferies($results);
    }

    public function getFilteredByOrgId(int $organizationId): ?array
    {
        $results = RefereeOnOrganization::query()
            ->join('user', 'user.user_id', '=', 'referee_on_organization.user_id')
            ->where('organization_id', '=', $organizationId)
            ->get($this->dateForCreateReferee);

        return $this->getReferies($results);
    }

    public function getByEmailAndOrgId(string $email, int $orgId):?RefereeOnOrganizationModel
    {
        $results = RefereeOnOrganization::query()
            ->join('user', 'user.user_id', '=', 'referee_on_organization.user_id')
            ->where('user.email',  '=', $email)
            ->where('organization_id', '=', $orgId)
            ->get($this->dateForCreateReferee);

        if (count($results) == 0){
            return null;
        }

        return $this->getReferies($results)[0];
    }

    /**@var $model RefereeOnOrganizationModel*/
    public function add(IModel $model): int
    {
        return RefereeOnOrganization::query()->create([
            'organization_id' => $model->getOrganizationId(),
            'user_id' => $model->getUserId()
        ])->getAttribute('referee_on_organization_id');
    }

    public function delete(int $id)
    {
        RefereeOnOrganization::query()
            ->where('referee_on_organization_id', '=', $id)
            ->delete();
    }

    public function update(IModel $model)
    {
        // TODO: Implement update() method.
    }

    private function getReferies($results)
    {
        $referies = [];

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

            $secretaries[] = new RefereeOnOrganizationModel($result['referee_on_organization_id'], $result['organization_id'], $user->getId() ,$user);
        }

        return $secretaries;
    }
}