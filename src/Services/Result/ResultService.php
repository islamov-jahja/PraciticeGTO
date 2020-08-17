<?php

namespace App\Services\Result;
use App\Domain\Models\AgeCategory\AgeCategory;
use App\Domain\Models\Event\Event;
use App\Domain\Models\EventParticipant\EventParticipant;
use App\Domain\Models\Result\ResultOnTrialInEvent;
use App\Domain\Models\Trial;
use App\Persistance\Repositories\AgeCategory\AgeCategoryRepository;
use App\Persistance\Repositories\Event\EventRepository;
use App\Persistance\Repositories\EventParticipant\EventParticipantRepository;
use App\Persistance\Repositories\LocalAdmin\LocalAdminRepository;
use App\Persistance\Repositories\Referee\RefereeInTrialOnEventRepository;
use App\Persistance\Repositories\Result\ResultRepository;
use App\Persistance\Repositories\Role\RoleRepository;
use App\Persistance\Repositories\Secretary\SecretaryOnOrganizationRepository;
use App\Persistance\Repositories\Secretary\SecretaryRepository;
use App\Persistance\Repositories\SportObject\SportObjectRepository;
use App\Persistance\Repositories\Team\TeamRepository;
use App\Persistance\Repositories\TrialRepository\TableInEventRepository;
use App\Persistance\Repositories\TrialRepository\TableRepository;
use App\Persistance\Repositories\TrialRepository\TrialInEventRepository;
use App\Persistance\Repositories\TrialRepository\TrialRepository;
use App\Persistance\Repositories\User\UserRepository;
use App\Services\Presenters\TrialsToResponsePresenter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ResultService
{
    private const GOLD = 'золото';
    private const SILVER = 'серебро';
    private const BRONZE = 'бронза';
    private const RESULT_FOR_BRONZE = 25;
    private const RESULT_FOR_SILVER = 40;
    private const RESULT_FOR_GOLD = 60;
    private $eventRepository;
    private $localAdminRepository;
    private $roleRepository;
    private $secretaryRepository;
    private $userRepository;
    private $eventParticipantRepository;
    private $secretaryOnOrgRepository;
    private $tableInEventRepository;
    private $tableRepository;
    private $trialRepository;
    private $trialInEventRepository;
    private $sportObjectRepository;
    private $refereeInTrialOnEventRepository;
    private $resultRepository;
    private $teamRepository;
    private $ageCategoryRepository;

    public function __construct(
        LocalAdminRepository $localAdminRepository,
        EventRepository $eventRepository,
        SecretaryRepository $secretaryRepository,
        RoleRepository $roleRepository,
        UserRepository $userRepository,
        EventParticipantRepository $eventParticipantRepository,
        SecretaryOnOrganizationRepository $secretaryOnOrgRepository,
        TableInEventRepository $tableInEventRepository,
        TableRepository $tableRepository,
        TrialRepository $trialRepository,
        TrialInEventRepository $trialInEventRepository,
        SportObjectRepository $sportObjectRepository,
        RefereeInTrialOnEventRepository $refereeInTrialOnEventRepository,
        ResultRepository $resultRepository,
        TeamRepository $teamRepository,
        AgeCategoryRepository $ageCategoryRepository
    )
    {
        $this->localAdminRepository = $localAdminRepository;
        $this->eventRepository = $eventRepository;
        $this->roleRepository = $roleRepository;
        $this->secretaryRepository = $secretaryRepository;
        $this->userRepository = $userRepository;
        $this->eventParticipantRepository = $eventParticipantRepository;
        $this->secretaryOnOrgRepository = $secretaryOnOrgRepository;
        $this->tableInEventRepository = $tableInEventRepository;
        $this->tableRepository = $tableRepository;
        $this->trialRepository = $trialRepository;
        $this->trialInEventRepository = $trialInEventRepository;
        $this->sportObjectRepository = $sportObjectRepository;
        $this->refereeInTrialOnEventRepository = $refereeInTrialOnEventRepository;
        $this->resultRepository = $resultRepository;
        $this->teamRepository = $teamRepository;
        $this->ageCategoryRepository = $ageCategoryRepository;
    }

    public function getResultsUfUserInEvent(int $eventId, int $userId)
    {
        $event = $this->eventRepository->get($eventId);
        if ($event == null){
            return [];
        }
        $user = $this->userRepository->get($userId);
        if ($user == null){
            return [];
        }

        $eventParticipant = $this->eventParticipantRepository->getByEmailAndEvent($user->getEmail(), $eventId);
        if ($eventParticipant == null){
            return [];
        }

        $listOfAllTrials = $this->trialRepository->getList($user->getGender(), $user->getAge());
        if (count($listOfAllTrials) == 0){
            return [];
        }

        $listTrialsOnEvent = $this->trialInEventRepository->getFilteredByEventId($eventId);

        $responseList = [];
        $ageCategory = $this->trialRepository->getNameOfAgeCategory($user->getAge());
        if ($event->getStatus() == Event::LEAD_UP){
            $trials = $this->getFilteredFromAllTrialsTrialsOnEvent($listOfAllTrials, $listTrialsOnEvent);
            $responseList = TrialsToResponsePresenter::getView($trials, []);
        }

        $badge = null;
        $dateAboutCountOfTest = $this->getDataAboutCountOfTests($user->getGender(), $this->ageCategoryRepository->getFilteredByName($ageCategory));
        if ($event->getStatus() != Event::LEAD_UP){
            $results = $this->resultRepository->getFilteredByUserIdAndEventId($userId, $eventId);
            $trials = $this->getFilteredFromAllTrialsTrialsOnEvent($listOfAllTrials, $listTrialsOnEvent);
            $responseList = TrialsToResponsePresenter::getView($trials, $this->getArrayWithResultsForTrials($results));
            $badge = $this->getBadgeOfUser($responseList, $dateAboutCountOfTest);
        }

        $teamName = null;
        if ($eventParticipant->getTeamId() != null){
            $team = $this->teamRepository->get($eventParticipant->getTeamId());
            $teamName = $team->getName();
        }

        return [
            'groups' => $responseList,
            'ageCategory' => $ageCategory,
            'badge' => $badge,
            'countTestsForBronze' => $dateAboutCountOfTest['countTestsForBronze'] ?? null,
            'countTestForSilver' => $dateAboutCountOfTest['countTestForSilver'] ?? null,
            'countTestsForGold' => $dateAboutCountOfTest['countTestsForGold'] ?? null,
            'orgId' => $event->getIdOrganization(),
            'eventId' => $eventId,
            'id' => $user->getId(),
            'name' => $user->getName(),
            'teamName' => $teamName,
            'teamId' => $eventParticipant->getTeamId(),
            'dateOfBirth' => $user->getDateOfBirth()
        ];
    }

    public function getBadgeOfUser(array $results, array $dateAboutCountOfTest)
    {
        $badge = null;
        if (!$this->allRequiredTestsCompleted($results)){
            return null;
        }

        $arrayWithCountOfBadges = $this->getArrayWithCountOfBadges($results);
        $countOfGold = (int)$arrayWithCountOfBadges['countOfGold'];
        $countOfSilver = (int)$arrayWithCountOfBadges['countOfSilver'];
        $countOfBronze = (int)$arrayWithCountOfBadges['countOfBronze'];

        if ($countOfGold >= $dateAboutCountOfTest['countTestsForGold']){
            return self::GOLD;
        }

        if (($countOfGold + $countOfSilver) >= $dateAboutCountOfTest['countTestForSilver']){
            return self::SILVER;
        }

        if (($countOfGold + $countOfSilver + $countOfBronze) >= $dateAboutCountOfTest['countTestsForBronze']){
            return self::BRONZE;
        }

        return $badge;
    }

    private function getArrayWithCountOfBadges(array $results):array
    {
        $countOfGold = 0;
        $countOfSilver = 0;
        $countOfBronze = 0;

        foreach ($results as $result){
            $badge = null;
            foreach ($result['group'] as $item){
                if ($item['badge'] == self::GOLD){
                    $badge = self::GOLD;
                }

                if ($item['badge'] == self::SILVER && $badge != self::GOLD){
                    $badge = self::SILVER;
                }

                if ($item['badge'] == self::BRONZE && $badge != self::GOLD && $badge != self::SILVER){
                    $badge = self::BRONZE;
                }
            }
            if ($badge == self::GOLD){
                $countOfGold++;
            }

            if ($badge == self::SILVER){
                $countOfSilver++;
            }

            if ($badge == self::BRONZE){
                $countOfBronze++;
            }
        }

        return [
            'countOfGold' => $countOfGold,
            'countOfSilver' => $countOfSilver,
            'countOfBronze' => $countOfBronze
        ];
    }

    private function allRequiredTestsCompleted(array $results):bool
    {
        foreach ($results as $result){
            if ($result['necessary']){
                $checked = false;
                foreach ($result['group'] as $trial){
                    if ($trial['secondResult'] != null && $trial['secondResult'] != 0){
                        $checked = true;
                    }
                }

                if (!$checked){
                    return false;
                }
            }
        }

        return true;
    }

    public function updateResult(int $resultTrialInEventId, string $firstResult)
    {
        $badge = null;
        $result = $this->resultRepository->get($resultTrialInEventId);
        $secondResult = $this->trialRepository->getSecondResult($firstResult, $result->getResultGuideId());
        $result->setBadge(null);

        if ($secondResult >= self::RESULT_FOR_BRONZE){
            $result->setBadge('бронза');
        }

        if ($secondResult >= self::RESULT_FOR_SILVER){
            $result->setBadge('серебро');
        }

        if ($secondResult >= self::RESULT_FOR_GOLD){
            $result->setBadge('золото');
        }
        $result->setFirstResult($firstResult);
        $result->setSecondResult($secondResult);
        $this->resultRepository->update($result);
    }

    public function getAllResultsInXlsxFormat(int $eventId)
    {
        if (file_exists(__DIR__.'/../../../public/'.$eventId.'.xlsx')){
            return 'http://petrodim.beget.tech/public/'.$eventId.'.xlsx';
        }

        $trialsInEvent = $this->trialInEventRepository->getFilteredByEventId($eventId);
        $results = [];

        foreach ($trialsInEvent as $trialInEvent){
            $results[] = $this->getResultsForTrial($trialInEvent->getTrialInEventId());
        }

        $participants = $this->eventParticipantRepository->getAllByEventId($eventId);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValueByColumnAndRow(1, 1, 'ID');
        $sheet->setCellValueByColumnAndRow(2, 1, 'UID гто');
        $sheet->setCellValueByColumnAndRow(3, 1, 'Ф.И.О.');
        $sheet->setCellValueByColumnAndRow(4, 1, 'Год рождения');
        $sheet->setCellValueByColumnAndRow(5, 1, 'Команда');
        $sheet->setCellValueByColumnAndRow(6, 1, 'Знак гто');

        $this->setAllTrials($sheet, $results);
        $this->setAllParticipants($sheet, $participants, count($results));

        $index = $this->getIndexOfTrialOnSheet('Прыжок в длину с места толчком двумя ногами (см)', $sheet, count($results));
        $writer = new Xlsx($spreadsheet);
        $writer->save($eventId.'.xlsx');

        return 'http://petrodim.beget.tech/public/'.$eventId.'.xlsx';
    }



    private function getIndexOfTrialOnSheet(string $trialName, Worksheet $sheet, int $allCount){
        $index = 7;
        for ($i = $index; $i<= $allCount*3 + 7; $i++){
            if ($sheet->getCellByColumnAndRow($index, 1)->getValue() == $trialName){
                return $index;
            }

            $index++;
        }

        return -1;
    }

    private function setAllTrials(Worksheet $sheet, array $results)
    {
        $i = 7;

        foreach ($results as $result){
            $sheet->mergeCellsByColumnAndRow($i, 1, $i+1, 1);
            $sheet->mergeCellsByColumnAndRow($i, 1, $i+2, 1);

            $sheet->setCellValueByColumnAndRow($i, 1, $result['trialName']);
            $sheet->setCellValueByColumnAndRow($i, 2,'первичный результат');
            $sheet->setCellValueByColumnAndRow($i+1, 2,'вторичный результат');
            $sheet->setCellValueByColumnAndRow($i+2, 2,'знак');

            $i += 3;
        }
    }

    /**@param $trials Trial[]*/
    private function getTrialFromResultGuideWithDataToBadge($trialId, array $trials)
    {
        foreach ($trials as $trial){
            if ($trial->getTrialId() == $trialId){
                return $trial;
            }
        }

        return null;
    }

    private function getDataAboutCountOfTests(int $gender, AgeCategory $ageCategory)
    {
        if ($gender == 0){
            return [
                'countTestsForBronze' => $ageCategory->getCountTestForBronzeForWoman(),
                'countTestForSilver' => $ageCategory->getCountTestFromSilverForWoman(),
                'countTestsForGold' => $ageCategory->getCountTestsForGoldForWoman()
            ];
        }

        if ($gender == 1){
            return [
                'countTestsForBronze' => $ageCategory->getCountTestForBronzeForMan(),
                'countTestForSilver' => $ageCategory->getCountTestFromSilverForMan(),
                'countTestsForGold' => $ageCategory->getCountTestsForGoldForMan()
            ];
        }
    }

    public function getAllResults(int $eventId)
    {
        $trialsInEvent = $this->trialInEventRepository->getFilteredByEventId($eventId);
        $results = [];

        foreach ($trialsInEvent as $trialInEvent){
            $results[] = $this->getResultsForTrial($trialInEvent->getTrialInEventId());
        }

        $participants = $this->eventParticipantRepository->getAllByEventId($eventId);
        $participantsResults = [];

        foreach ($participants as $participant){
            $resultBadge = $this->getResultsUfUserInEvent($eventId, $participant->getUser()->getId());
            $participantsResults[] = ['userId' => $participant->getUser()->getId(), 'badge' => $resultBadge['badge'] ];
        }

        return [
          'trials' => $results,
          'participants' => $participantsResults
        ];
    }

    public function getResultsForTrial(int $trialInEventId)
    {
        $trialInEvent = $this->trialInEventRepository->get($trialInEventId);
        if ($trialInEvent == null){
            return [];
        }

        $event = $this->eventRepository->get($trialInEvent->getEventId());

        $trialId = $trialInEvent->getTrial()->getTrialId();
        $eventParticipants = $this->eventParticipantRepository->getAllByEventId($event->getId());

        $eventParticipantsForTrial = [];
        $ParticipantsInTrial = $this->getParticipantsForTrial($eventParticipants, $event->getId(), $trialId);
        $results = [];

        if ($event->getStatus() == Event::LEAD_UP) {
            foreach ($ParticipantsInTrial as $participant){
                $team = $this->teamRepository->get($participant->getTeamId() ?? -1);
                if ($team == null){
                    $teamName = null;
                }else{
                    $teamName = $team->getName();
                }

                $results[] = [
                    'resultOfTrialInEventId' => null,
                    'userId' => $participant->getUser()->getId(),
                    'userName' => $participant->getUser()->getName(),
                    'teamId' => $participant->getTeamId(),
                    'teamName' => $teamName,
                    'dateOfBirth' => $participant->getUser()->getDateOfBirth(),
                    'gender' => $participant->getUser()->getGender(),
                    'firstResult' => null,
                    'secondResult' => null,
                    'badge' => null
                ];
            }
        }
        $trial = $this->trialRepository->get($trialId);
        if ($event->getStatus() != Event::LEAD_UP){
            foreach ($ParticipantsInTrial as $participant){
                $team = $this->teamRepository->get($participant->getTeamId() ?? -1);
                if ($team == null){
                    $teamName = null;
                }else{
                    $teamName = $team->getName();
                }

                $resultOfTrialOnEvent = $this->resultRepository->getFilteredByUserIdEventIdTrialId($participant->getUser()->getId(), $event->getId(), $trialId);
                $results[] = [
                    'resultOfTrialInEventId' => $resultOfTrialOnEvent->getResultTrialInEventId(),
                    'userId' => $participant->getUser()->getId(),
                    'userName' => $participant->getUser()->getName(),
                    'teamId' => $participant->getTeamId(),
                    'teamName' => $teamName,
                    'dateOfBirth' => $participant->getUser()->getDateOfBirth(),
                    'gender' => $participant->getUser()->getGender(),
                    'firstResult' => $resultOfTrialOnEvent->getFistResult(),
                    'secondResult' => $resultOfTrialOnEvent->getSecondResult(),
                    'badge' => $resultOfTrialOnEvent->getBadge()
                ];
            }
        }

        return [
            'participants' => $results,
            'trialName' => $trial->getName(),
            'isTypeTime' => $trial->isTypeTime(),
            'eventStatus' => $event->getStatus(),
            'orgId' => $event->getIdOrganization(),
            'eventId' => $event->getId()
        ];
    }

    /**
     * @param $eventParticipants EventParticipant[]
     * @param int $eventId
     * @param int $trialId
     * @return EventParticipant[]
     */
    private function getParticipantsForTrial(array $eventParticipants, int $eventId, int $trialId)
    {
        $participants = [];
        $listTrialsOnEvent = $this->trialInEventRepository->getFilteredByEventId($eventId);
        foreach ($eventParticipants as $eventParticipant){
            $listOfAllTrials = $this->trialRepository->getList($eventParticipant->getUser()->getGender(), $eventParticipant->getUser()->getAge());
            if (count($listOfAllTrials) == 0){
                continue;
            }

            $trials = $this->getFilteredFromAllTrialsTrialsOnEvent($listOfAllTrials, $listTrialsOnEvent);
            foreach ($trials as $trial){
                if ($trial->getTrialId() == $trialId){
                    $participants[] = $eventParticipant;
                    break;
                }
            }
        }

        return $participants;
    }

    /**@param $listOfAllTrials Trial[]*/
    /**@param  $listTrialsOEvent Trial\TrialInEvent[]*/
    private function getFilteredFromAllTrialsTrialsOnEvent(array $listOfAllTrials, array $listTrialsOEvent)
    {
        $response = [];
        foreach ($listOfAllTrials as $trial){
            foreach ($listTrialsOEvent as $trialOnEvent){
                if ($trial->getTrialId() == $trialOnEvent->getTrial()->getTrialId()){
                    $response[] = $trial;
                }
            }
        }

        return $response;
    }

    /**@param  $results ResultOnTrialInEvent[]*/
    private function getArrayWithResultsForTrials(array $results)
    {
        $arrayWithResults = [];
        foreach ($results as $result){
            $arrayWithResults[$result->getTrialInEvent()->getTrial()->getTrialId()] = [
                'firstResult' => $result->getFistResult(),
                'secondResult' => $result->getSecondResult(),
                'badge' => $result->getBadge(),
                'resultTrialInEventId' => $result->getResultTrialInEventId()
            ];
        }

        return $arrayWithResults;
    }

    /**@param EventParticipant[] $participants */
    private function setAllParticipants(Worksheet $sheet, array $participants, int $allCount)
    {
        $index = 3;
        foreach ($participants as $eventParticipant){
            $teamName = '';
            if ($eventParticipant->getTeamId() != null){
                $teamName = $this->teamRepository->get($eventParticipant->getTeamId())->getName();
            }

            $resultOfUser = $this->getResultsUfUserInEvent($eventParticipant->getEventId(), $eventParticipant->getUser()->getId());
            $sheet->setCellValueByColumnAndRow(1, $index, $eventParticipant->getUser()->getId());
            $sheet->setCellValueByColumnAndRow(2, $index, $eventParticipant->getUser()->getUid());
            $sheet->setCellValueByColumnAndRow(3, $index, $eventParticipant->getUser()->getName());
            $sheet->setCellValueByColumnAndRow(4, $index, $resultOfUser['dateOfBirth']);
            $sheet->setCellValueByColumnAndRow(5, $index, $teamName);
            $sheet->setCellValueByColumnAndRow(6, $index, $resultOfUser['badge'] ?? '');

            foreach ($resultOfUser['groups'] as $group){
                foreach ($group['group'] as $trial){
                    $indexTrial = $this->getIndexOfTrialOnSheet($trial['trialName'], $sheet, $allCount);
                    $sheet->setCellValueByColumnAndRow($indexTrial, $index, $trial['firstResult']);
                    $sheet->setCellValueByColumnAndRow($indexTrial+1, $index, $trial['secondResult']);
                    $sheet->setCellValueByColumnAndRow($indexTrial+2, $index, $trial['badge']);
                }
            }
            $index++;
        }
    }
}