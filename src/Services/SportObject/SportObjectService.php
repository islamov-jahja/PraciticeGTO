<?php

namespace App\Services\SportObject;
use App\Domain\Models\SportObject\SportObject;
use App\Persistance\Repositories\SportObject\SportObjectRepository;

class SportObjectService
{
    private $sportObjectRepository;
    public function __construct(SportObjectRepository $sportObjectRepository)
    {
        $this->sportObjectRepository = $sportObjectRepository;
    }

    public function addToOrganization(SportObject $sportObject)
    {
        return $this->sportObjectRepository->add($sportObject);
    }

    public function delete(int $sportObjectId)
    {
        $this->sportObjectRepository->delete($sportObjectId);
    }

    public function getFilteredByOrganization(int $organizationId)
    {
        return $this->sportObjectRepository->getForOrganization($organizationId);
    }

    public function update(SportObject $sportObject)
    {
        $this->sportObjectRepository->update($sportObject);
    }
}