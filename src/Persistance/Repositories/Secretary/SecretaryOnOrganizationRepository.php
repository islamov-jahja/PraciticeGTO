<?php


namespace App\Persistance\Repositories\Secretary;


use App\Domain\Models\Event\Event;
use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Domain\Models\Secretary\SecretaryOnOrganization;
use App\Domain\Models\User\UserCreater;
use App\Persistance\ModelsEloquant\Secretary\Secretary as SecretaryPDO;
use App\Persistance\ModelsEloquant\Secretary\SecretaryOnOrganization AS SecretaryOnOrgPdo;
use DateTime;

class SecretaryOnOrganizationRepository implements IRepository
{

    private $dateForCreateSecretary = [
        'secretary_on_organization_id',
        'organization_id',
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
        $results = SecretaryOnOrgPdo::query()
            ->join('user', 'user.user_id', '=', 'secretary_on_organization.user_id')
            ->where('secretary_on_organization_id',  '=', $id)
            ->get($this->dateForCreateSecretary);

        if (count($results) == 0){
            return null;
        }

        return $this->getSecretaries($results)[0];
    }

    /**
     * @inheritDoc
     */
    public function getAll(): ?array
    {
        // TODO: Implement getAll() method.
    }


    /**@var $model SecretaryOnOrganization*/
    public function add(IModel $model): int
    {
        return SecretaryOnOrgPdo::query()->create([
            'organization_id' => $model->getOrganizationId(),
            'user_id' => $model->getUser()->getId()
        ])->getAttribute('secretary_on_organization_id');
    }

    public function delete(int $id)
    {
        SecretaryOnOrgPdo::query()->where([
            'secretary_on_organization_id' => $id
        ])->delete();
    }

    public function update(IModel $model)
    {
        // TODO: Implement update() method.
    }

    public function getByEmailAndOrgId(string $email, int $orgId):?SecretaryOnOrganization
    {
        $results = SecretaryOnOrgPdo::query()
            ->join('user', 'user.user_id', '=', 'secretary_on_organization.user_id')
            ->where('user.email',  '=', $email)
            ->where('organization_id', '=', $orgId)
            ->get($this->dateForCreateSecretary);

        if (count($results) == 0){
            return null;
        }

        return $this->getSecretaries($results)[0];
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

            $secretaries[] = new SecretaryOnOrganization($result['secretary_on_organization_id'], $result['user_id'], $result['organization_id'], $user);
        }

        return $secretaries;
    }

    /**@return SecretaryOnOrganization[]*/
    public function getByUserId(int $userId):array
    {
        $results = SecretaryOnOrgPdo::query()
            ->join('user', 'user.user_id', '=', 'secretary_on_organization.user_id')
            ->where('user.user_id',  '=', $userId)
            ->get($this->dateForCreateSecretary);

        return $this->getSecretaries($results);
    }

    /**@return SecretaryOnOrganization[]*/
    public function getByOrgId(int $organizationId)
    {
        $results = SecretaryOnOrgPdo::query()
            ->join('user', 'user.user_id', '=', 'secretary_on_organization.user_id')
            ->where('organization_id',  '=', $organizationId)
            ->get($this->dateForCreateSecretary);

        return $this->getSecretaries($results);
    }
}