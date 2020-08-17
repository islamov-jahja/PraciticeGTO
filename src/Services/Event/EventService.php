<?php

namespace App\Services\Event;
use App\Application\Actions\ActionError;
use App\Application\Middleware\AuthorizeMiddleware;
use App\Domain\Models\EventParticipant\EventParticipant;
use App\Domain\Models\Result\ResultOnTrialInEvent;
use App\Domain\Models\Secretary\Secretary;
use App\Domain\Models\Trial;
use App\Domain\Models\Trial\TableInEvent;
use App\Domain\Models\Trial\TrialInEvent;
use App\Persistance\Repositories\Event\EventRepository;
use App\Persistance\Repositories\EventParticipant\EventParticipantRepository;
use App\Persistance\Repositories\LocalAdmin\LocalAdminRepository;
use App\Domain\Models\Event\Event;
use App\Persistance\Repositories\Referee\RefereeInTrialOnEventRepository;
use App\Persistance\Repositories\Result\ResultRepository;
use App\Persistance\Repositories\Role\RoleRepository;
use App\Persistance\Repositories\Secretary\SecretaryOnOrganizationRepository;
use App\Persistance\Repositories\Secretary\SecretaryRepository;
use App\Persistance\Repositories\SportObject\SportObjectRepository;
use App\Persistance\Repositories\TrialRepository\TableInEventRepository;
use App\Persistance\Repositories\TrialRepository\TableRepository;
use App\Persistance\Repositories\TrialRepository\TrialInEventRepository;
use App\Persistance\Repositories\TrialRepository\TrialRepository;
use App\Persistance\Repositories\User\UserRepository;
use App\Services\EmailSendler\EmailSendler;
use DateTime;
use Psr\Http\Message\ResponseInterface;

class EventService
{
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
    private $emailSender;

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
        EmailSendler $emailSendler
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
        $this->emailSender = $emailSendler;
    }

    public function add(Event $event, string $userEmail, ResponseInterface $response)
    {
        if (!$this->localAdminRepository->localAdminIsSetOnDB($userEmail, $event->getIdOrganization())){
            return $response->withStatus(403);
        }

        return $this->eventRepository->add($event);
    }

    public function delete(int $organizationId, int $eventId, ResponseInterface $response, string $userEmail)
    {
        $response = $this->getInitedResponseWithStatusIfErrorOfAccess($response, $userEmail, $organizationId, $eventId);
        if ($response->getStatusCode() != 200){
            return $response;
        }

        $this->eventRepository->delete($eventId);
    }

    /**@var $admins Secretary[]*/
    private function changeAdminStatusToSimple(?array $admins, $simpleRoleId)
    {
        if ($admins == null){
            return;
        }

        foreach ($admins as $admin){
            $user = $admin->getUser();
            $secretaryInEvents = $this->secretaryRepository->getFilteredByUserEmail($user->getEmail());

            if (count($secretaryInEvents) == 1) {
                $user->setRoleId($simpleRoleId);
                $this->userRepository->update($user);
            }
        }
    }

    public function get(int $organizationId, int $eventId, ResponseInterface $response)
    {
        /**@var $event Event*/
        $event = $this->eventRepository->get($eventId);
        if ($event->getIdOrganization() != $organizationId){
            return $response->withStatus(400);
        }

        return $this->eventRepository->get($eventId);
    }

    private function getInitedResponseWithStatusIfErrorOfAccess(ResponseInterface $response, string $userEmail, int $organizationId, int $eventId)
    {
        if (!$this->localAdminRepository->localAdminIsSetOnDB($userEmail, $organizationId)){
            return $response->withStatus(403);
        }

        /**@var $event Event*/
        $event = $this->eventRepository->get($eventId);
        if ($event->getIdOrganization() != $organizationId){
            return $response->withStatus(403);
        }

        return $response->withStatus(200);
    }

    public function getAll(int $organizationId)
    {
        return $this->eventRepository->getAllFilteredByOrganizationId($organizationId);
    }

    public function update(Event $event, string $userEmail, ResponseInterface $response)
    {
        $this->eventRepository->update($event);

        return $response;
    }

    public function applyToEvent(int $eventId, string $userEmail, bool $confirmed, $teamId = null)
    {
        $user = $this->userRepository->getByEmail($userEmail);
        $participant = new EventParticipant(-1, $eventId, $user->getId(), $confirmed, $user, $teamId);
        $event = $this->eventRepository->get($eventId);

        $users = [];
        $localAdmins = $this->localAdminRepository->getFilteredByOrgId($event->getIdOrganization());
        $secretaries = $this->secretaryRepository->getFilteredByEventId($eventId);

        if ($localAdmins != null) {
            foreach ($localAdmins as $localAdmin) {
                $users[] = $localAdmin->getUser()->getEmail();
            }
        }

        if ($secretaries != null) {
            foreach ($secretaries as $secretary) {
                $users[] = $secretary->getUser()->getEmail();
            }
        }

        $message = EmailSendler::$MESSAGE_FOR_PEOPLE_WHEN_SPORTSMEN_SEND_INVITE;
        $message = str_replace('user_name', $user->getName(), $message);
        $message = str_replace('user_id', $user->getId(), $message);
        $message = str_replace('event_name', $event->getName(), $message);
        $this->emailSender->sendMessage($users, $message);
        return $this->eventParticipantRepository->add($participant);
    }

    public function getForSecretary(string $userEmail)
    {
        $secretaries = $this->secretaryRepository->getFilteredByUserEmail($userEmail);
        $events = [];

        if ($secretaries == null){
            return [];
        }
        
        foreach ($secretaries as $secretary){
            $secretaryInOrg = $this->secretaryOnOrgRepository->getByEmailAndOrgId($secretary->getUser()->getEmail(), $secretary->getOrganizationId());
            if ($secretaryInOrg != null) {
                $events[] = $this->eventRepository->get($secretary->getEventId());
            }
        }

        return $events;
    }

    /**@return Event[]*/
    public function getForUser(string $userEmail):array
    {
        $participants = $this->eventParticipantRepository->getByEmail($userEmail);
        $events = [];
        foreach ($participants as $participant){
            $event = $this->eventRepository->get($participant->getEventId())->toArray();
            $event['userConfirmed'] = $participant->isConfirmed();
            $events[] = $event;
        }

        return $events;
    }

    public function getTable(int $eventId)
    {
        return $this->tableInEventRepository->getFilteredByEventId($eventId);
    }

    public function addTable(int $eventId, int $tableId)
    {
        $table = $this->tableRepository->get($tableId);
        $tableInEvent = new TableInEvent(-1, $eventId, $table);
        return $this->tableInEventRepository->add($tableInEvent);
    }

    public function getFreeTrials(int $eventId)
    {
        $tableInEvent = $this->tableInEventRepository->getFilteredByEventId($eventId);
        if ($tableInEvent == null){
            return [];
        }

        $trials = $this->trialRepository->getFilteredByTableId($tableInEvent->getTable()->getTableId());
        $response = [];
        foreach ($trials as $trial){
            $ageCategories = $this->trialRepository->getAgeCategoriesForTrialId($trial->getTrialId());
            $trialInArray = $trial->toArray();
            $trialInArray['ageCategories'] = $ageCategories;
            $response[] = $trialInArray;
        }

        return $response;
    }

    public function addTrialToEventFromTable(int $eventId, int $trialId, int $sportObjectId, DateTime $startDateTime)
    {
        $trial = $this->trialRepository->get($trialId);
        $sportObject = $this->sportObjectRepository->get($sportObjectId);
        $trialInEvent = new TrialInEvent(-1, $trial, $eventId, $sportObject, $startDateTime);
        return $this->trialInEventRepository->add($trialInEvent);
    }

    public function getTrialsOnEvent(int $eventId)
    {
        $trialsInEvent = $this->trialInEventRepository->getFilteredByEventId($eventId);

        foreach ($trialsInEvent as $trialInEvent){
            $referies = $this->refereeInTrialOnEventRepository->getFilteredByTrialOnEventId($trialInEvent->getTrialInEventId());
            $trialInEvent->setReferies($referies);
        }

        return $trialsInEvent;
    }

    public function unsubscribe(string $userEmail, int $eventId)
    {
        $participant = $this->eventParticipantRepository->getByEmailAndEvent($userEmail, $eventId);
        $this->eventParticipantRepository->delete($participant->getEventParticipantId());
    }

    public function deleteTrialFromEvent(int $trialInEventId)
    {
        $this->trialInEventRepository->delete($trialInEventId);
    }

    public function changeStatusOfEvent(int $eventId)
    {
        $event = $this->eventRepository->get($eventId);

        if ($event->getStatus() == Event::HOLDING){
            $event->setStatus(Event::COMPLETED);
            $this->eventRepository->update($event);
        }

        if ($event->getStatus() == Event::PREPARADNESS){
            $event->setStatus(Event::HOLDING);
            $this->eventRepository->update($event);
        }

        if ($event->getStatus() == Event::LEAD_UP) {
            $eventParticipants = $this->eventParticipantRepository->getAllByEventId($eventId);
            $this->confirmParticipants($eventParticipants);
            $this->createResultsForAllParticipants($eventParticipants, $eventId);
            $event->setStatus(Event::PREPARADNESS);
            $this->eventRepository->update($event);
        }
    }

    /**
     * @param $participants EventParticipant[]
     * @param int $eventId
     */
    private function createResultsForAllParticipants(array $participants, int $eventId)
    {
        foreach ($participants as $participant){
            $user = $participant->getUser();
            $listOfAllTrials = $this->trialRepository->getList($user->getGender(), $user->getAge());
            $listTrialsOnEvent = $this->trialInEventRepository->getFilteredByEventId($eventId);
            $trials = $this->getFilteredFromAllTrialsTrialsOnEvent($listOfAllTrials, $listTrialsOnEvent);
            foreach ($trials as $trial){
                $result = new ResultOnTrialInEvent($this->trialInEventRepository->getFilteredByTrialId($trial->getTrialId(), $eventId), $user, $trial->getResultGuideId(), null, null, null, -1);
                $this->resultRepository->add($result);
            }
        }
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

    /**@param $participants EventParticipant[]*/
    private function confirmParticipants(array $participants)
    {
        foreach ($participants as $participant){
            if ($participant->isConfirmed()){
                continue;
            }

            $participant->doConfirm();
            $this->eventParticipantRepository->update($participant);
        }
    }
}