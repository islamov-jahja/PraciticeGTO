<?php
namespace App\Services\Organization;

use App\Application\Middleware\AuthorizeMiddleware;
use App\Domain\Models\IRepository;
use App\Domain\Models\LocalAdmin\LocalAdmin;
use App\Domain\Models\Organization;
use App\Domain\Models\IModel;
use App\Persistance\Repositories\LocalAdmin\LocalAdminRepository;
use App\Persistance\Repositories\Organization\OrganizationRepository;
use App\Persistance\Repositories\Role\RoleRepository;
use App\Persistance\Repositories\Secretary\SecretaryRepository;
use App\Persistance\Repositories\User\UserRepository;
use Psr\Http\Message\ResponseInterface;

class OrganizationService
{
    private $organizationRepository;
    private $secretaryRepository;
    private $localAdminRepository;
    private $userRepository;
    private $roleRepository;

    public function __construct(OrganizationRepository $orgRep, SecretaryRepository $secretaryRepository, LocalAdminRepository $localAdminRepository, UserRepository $userRepository, RoleRepository $roleRepository)
    {
        $this->organizationRepository = $orgRep;
        $this->secretaryRepository = $secretaryRepository;
        $this->localAdminRepository = $localAdminRepository;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    public function addOrganization(Organization $organization)
    {
        $organizationToCheck = $this->organizationRepository->getFilteredByName($organization->getName());
        if ($organizationToCheck != null){
            return -1;
        }

        return $this->organizationRepository->add($organization);
    }

    public function deleteOrganization(int $id)
    {
        $roleIdOfSimpleUser = $this->roleRepository->getByName(AuthorizeMiddleware::SIMPLE_USER)->getId();
        $localAdminsInOrganization = $this->localAdminRepository->getFilteredByOrgId($id);
        $secretariesInOrganization = $this->secretaryRepository->getFilteredByOrgId($id);

        $this->changeAdminStatusToSimple($localAdminsInOrganization, $roleIdOfSimpleUser);
        $this->changeAdminStatusToSimple($secretariesInOrganization, $roleIdOfSimpleUser);

        $this->organizationRepository->delete($id);
    }

    private function changeAdminStatusToSimple(?array $admins, $simpleRoleId)
    {
        if ($admins == null){
            return;
        }

        foreach ($admins as $admin){
            $user = $admin->getUser();
            $user->setRoleId($simpleRoleId);
            $this->userRepository->update($user);
        }
    }

    public function getOrganization(int $id):?IModel
    {
        return $this->organizationRepository->get($id);
    }

    /**
     * @return IModel[]
     */
    public function getOrganizations():?array
    {
        return $this->organizationRepository->getAll();
    }

    public function update(Organization $organization)
    {
        $organizationToCheck = $this->organizationRepository->getFilteredByName($organization->getName());
        if ($organizationToCheck != null){
            if ($organization->getId() != $organizationToCheck->getId()) {
                return -1;
            }
        }

        $this->organizationRepository->update($organization);
        return 0;
    }
}