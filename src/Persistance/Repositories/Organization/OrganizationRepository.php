<?php
namespace App\Persistance\Repositories\Organization;
use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use \App\Domain\Models\Organization;
use App\Persistance\ModelsEloquant\Organization\Organization as OrgPDO;
use DateTime;

class OrganizationRepository implements IRepository
{

    public function get(int $id): ?IModel
    {
        $organization = OrgPDO::query()->where('organization_id', '=', $id)->get();
        if (count($organization) == 0){
            return null;
        }

        $organization = new Organization(
            $organization[0]['organization_id'],
            $organization[0]['name'],
            $organization[0]['address'],
            $organization[0]['leader'],
            $organization[0]['phone_number'],
            $organization[0]['OQRN'],
            $organization[0]['payment_account'],
            $organization[0]['branch'],
            $organization[0]['bik'],
            $organization[0]['correspondent_account']
        );

        $this->initCountOfEventsInOrganization($organization);

        return $organization;
    }

    private function initCountOfEventsInOrganization(Organization $organization)
    {
        $result = OrgPDO::query()->join('event', 'event.organization_id', '=', 'organization.organization_id')
            ->where('organization.organization_id','=', $organization->getId())
            ->get([
                'event.expiration_date'
            ]);

        if (count($result) == 0){
            $organization->setCountOfAllEvents(0);
            $organization->setCountOfActiveEvents(0);
            return;
        }

        $organization->setCountOfAllEvents(count($result));

        $countOfActiveEvents = 0;

        foreach ($result as $event){
            if ($event['expiration_date'] >= (new DateTime())->format('Y-m-d H:i:s')){
                $countOfActiveEvents++;
            }
        }

        $organization->setCountOfActiveEvents($countOfActiveEvents);
    }

    /**
     * @inheritDoc
     */
    public function getAll(): ?array
    {
        $organizations = OrgPDO::query()->get();

        if (count($organizations) == 0){
            return null;
        }

        $organizationsForResponse = [];
        foreach ($organizations as $organization){
            $organization = new Organization
            (
                $organization['organization_id'],
                $organization['name'],
                $organization['address'],
                $organization['leader'],
                $organization['phone_number'],
                $organization['OQRN'],
                $organization['payment_account'],
                $organization['branch'],
                $organization['bik'],
                $organization['correspondent_account']
            );

            $this->initCountOfEventsInOrganization($organization);

            $organizationsForResponse[] = $organization;
        }

        return $organizationsForResponse;

    }

    public function getFilteredByName(string $name):?IModel
    {
        $organizations = OrgPDO::query()->where('name', '=', $name)->get();

        if (count($organizations) == 0){
            return null;
        }

        $organization = new Organization
        (
            $organizations[0]['organization_id'],
            $organizations[0]['name'],
            $organizations[0]['address'],
            $organizations[0]['leader'],
            $organizations[0]['phone_number'],
            $organizations[0]['OQRN'],
            $organizations[0]['payment_account'],
            $organizations[0]['branch'],
            $organizations[0]['bik'],
            $organizations[0]['correspondent_account']
        );

        $this->initCountOfEventsInOrganization($organization);

        return $organization;
    }

    /**@var $model Organization*/
    public function add(IModel $model):int
    {
        $modelInArray = $model->toArray();
        unset($modelInArray['id']);
        $id = OrgPDO::query()->create($modelInArray);
        return $id->getAttribute('organization_id');
    }

    public function delete(int $id)
    {
        OrgPDO::query()->where('organization_id', '=', $id)->delete();
    }

    /**@var $organization Organization*/
    public function update(IModel $organization)
    {
        OrgPDO::query()->where('organization_id', '=', $organization->getId())->update([
            'name' => $organization->getName(),
            'address' => $organization->getAddress(),
            'leader' => $organization->getLeader(),
            'phone_number' => $organization->getPhoneNumber(),
            'OQRN' => $organization->getOqrn(),
            'payment_account' => $organization->getPaymentAccount(),
            'branch' => $organization->getBranch(),
            'bik' => $organization->getBik(),
            'correspondent_account' => $organization->getCorrespondentAccount()
        ]);
    }
}