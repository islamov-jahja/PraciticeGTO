<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 28.11.2019
 * Time: 7:25
 */

namespace App\Services\Trial;
use App\Domain\Models\Trial as TrialModel;
use App\Persistance\Repositories\TrialRepository\TableRepository;
use App\Persistance\Repositories\TrialRepository\TrialRepository;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\Presenters\TrialsToResponsePresenter;

class Trial
{
    private $trialRep;
    private $tableRepository;
    public function __construct(TrialRepository $trialRepository, TableRepository $tableRepository)
    {
        $this->trialRep = $trialRepository;
        $this->tableRepository = $tableRepository;
    }

    public function getTrialsByGenderAndAge(array $params, Response $response):Response
    {
        $trials = $this->trialRep->getList($params['gender'], $params['age']);

        $nameAgeCategory = $this->trialRep->getNameOfAgeCategory($params['age']);
        $response->getBody()->write(json_encode(['groups' => TrialsToResponsePresenter::getView($trials), 'ageCategory' => $nameAgeCategory]));
        return $response->withStatus(200);
    }

    public function getSecondResult(array $params, Response $response)
    {
        $params['trialId'] = $params['id'];
        $secondResult = $this->trialRep->getSecondResult($params['firstResult'], $params['trialId']);
        $response->getBody()->write(json_encode(['secondResult' => $secondResult]));
        return $response;
    }

    public function getAllFreeTables()
    {
        return $this->tableRepository->getAll();
    }
}