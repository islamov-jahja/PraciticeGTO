<?php

namespace App\Services\AccessService;
use App\Application\Actions\ActionError;
use App\Application\Middleware\AuthorizeMiddleware;
use App\Domain\Models\Event\Event;
use App\Domain\Models\EventParticipant\EventParticipant;
use App\Domain\Models\IModel;
use App\Domain\Models\LocalAdmin\LocalAdmin;
use App\Domain\Models\Organization;
use App\Domain\Models\Referee\RefereeOnOrganization;
use App\Domain\Models\Referee\RefereeOnTrialInEvent;
use App\Domain\Models\Secretary\Secretary;
use App\Domain\Models\Secretary\SecretaryOnOrganization;
use App\Domain\Models\User\User;
use App\Persistance\Repositories\Event\EventRepository;
use App\Persistance\Repositories\EventParticipant\EventParticipantRepository;
use App\Persistance\Repositories\LocalAdmin\LocalAdminRepository;
use App\Persistance\Repositories\Organization\OrganizationRepository;
use App\Persistance\Repositories\Referee\RefereeInTrialOnEventRepository;
use App\Persistance\Repositories\Referee\RefereeRepository;
use App\Persistance\Repositories\Result\ResultRepository;
use App\Persistance\Repositories\Role\RoleRepository;
use App\Persistance\Repositories\Secretary\SecretaryOnOrganizationRepository;
use App\Persistance\Repositories\Secretary\SecretaryRepository;
use App\Persistance\Repositories\SportObject\SportObjectRepository;
use App\Persistance\Repositories\Team\TeamRepository;
use App\Persistance\Repositories\TeamLead\TeamLeadRepository;
use App\Persistance\Repositories\TrialRepository\TableInEventRepository;
use App\Persistance\Repositories\TrialRepository\TableRepository;
use App\Persistance\Repositories\TrialRepository\TrialInEventRepository;
use App\Persistance\Repositories\User\UserRepository;

class AccessService
{
    private $userRepository;
    private $localAdminRepository;
    private $secretaryRepository;
    private $organizationRepository;
    private $rolRepository;
    private $eventRepository;
    private $errors;
    private $response;
    private $eventParticipantRepository;
    private $teamRepository;
    private $teamLeadRepository;
    private $secretaryOnOrganizationRepository;
    private $sportObjectRepository;
    private $refereeOnOrganizationRepository;
    private $tableInEventRepository;
    private $tableRepository;
    private $trialInEventRepository;
    private $refereeOnTrialInEventRepository;
    private $resultRepository;

    public function __construct
    (
        UserRepository $userRepository,
        LocalAdminRepository $localAdminRepository,
        SecretaryRepository $secretaryRepository,
        OrganizationRepository $organizationRepository,
        RoleRepository $roleRepository,
        EventRepository $eventRepository,
        EventParticipantRepository $eventParticipantRepository,
        TeamRepository $teamRepository,
        TeamLeadRepository $teamLeadRepository,
        SecretaryOnOrganizationRepository $secretaryOnOrganizationRepository,
        SportObjectRepository $sportObjectRepository,
        RefereeRepository $refereeRepository,
        TableInEventRepository $tableInEventRepository,
        TableRepository $tableRepository,
        TrialInEventRepository $trialInEventRepository,
        RefereeInTrialOnEventRepository $refereeOnTrialInEventRepository,
        ResultRepository $resultRepository
    )
    {
        $this->userRepository = $userRepository;
        $this->localAdminRepository = $localAdminRepository;
        $this->secretaryRepository = $secretaryRepository;
        $this->organizationRepository = $organizationRepository;
        $this->rolRepository = $roleRepository;
        $this->eventRepository = $eventRepository;
        $this->eventParticipantRepository = $eventParticipantRepository;
        $this->teamRepository = $teamRepository;
        $this->teamLeadRepository = $teamLeadRepository;
        $this->secretaryOnOrganizationRepository = $secretaryOnOrganizationRepository;
        $this->sportObjectRepository = $sportObjectRepository;
        $this->refereeOnOrganizationRepository = $refereeRepository;
        $this->tableInEventRepository = $tableInEventRepository;
        $this->tableRepository = $tableRepository;
        $this->trialInEventRepository = $trialInEventRepository;
        $this->refereeOnTrialInEventRepository = $refereeOnTrialInEventRepository;
        $this->resultRepository = $resultRepository;
        $this->errors = [];
        $this->response = true;
    }

    private function addError(ActionError $actionError)
    {
        $this->errors[] = $actionError;
    }

    private function getErrorsInJson():array
    {
        $errorsInJson = [];
        foreach ($this->errors as $error){
            $errorsInJson[] = $error->jsonSerialize();
        }

        return$errorsInJson;
    }

    public function hasAccessApplyToEvent(int $eventId, string $email)
    {
        $email = mb_strtolower($email);
        $event = $this->eventRepository->get($eventId);
        $participant = $this->eventParticipantRepository->getByEmailAndEvent($email, $eventId);
        $this->addErrorIfEventNoInLeadUpStatus($event);
        $this->addErrorIfEventNotExists($event);
        $this->addErrorIfParticipantExistOnEvent($participant);
        return $this->getResponse();
    }

    public function hasAccessUnsubscribeFromEvent(int $eventId, string $userEmail)
    {
        $participant = $this->eventParticipantRepository->getByEmailAndEvent($userEmail, $eventId);
        if ($participant == null){
            return false;
        }

        if ($participant->isConfirmed()){
            return false;
        }

        return true;
    }

    private function addErrorIfParticipantExistOnEvent(?EventParticipant $eventParticipant)
    {
        if ($eventParticipant != null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Вы уже подавали заявку на участие'));
        }
    }

    public function hasAccessAddParticipantToTeam(string $userEmail, string $userRole, int $teamId, string $emailParticipant)
    {
        $user = $this->userRepository->getByEmail($emailParticipant);
        $userEmail = mb_strtolower($userEmail);
        $team = $this->teamRepository->get($teamId);
        $event = $this->eventRepository->get($team->getEventId());
        $this->addErrorIfTeamNotExists($team);
        $this->addErrorIfParticipantExistOnThisEvent($emailParticipant, $event);
        $this->addErrorIfParticipantNotExists($user);
        switch ($userRole){
            case AuthorizeMiddleware::LOCAL_ADMIN:{
                return $this->localAdminHasAccessWorkWithParticipant($userEmail, $event);
            }
            case AuthorizeMiddleware::SECRETARY:{
                return $this->secretaryHasAccessWorkWithParticipant($userEmail, $event);
            }
            case AuthorizeMiddleware::TEAM_LEAD:{
                return $this->teamLeadHasAccessWorkWithParticipantOnTeam($teamId, $userEmail, $event);
            }
        }

        return false;
    }


    public function hasAccessAddParticipantToEvent(string $userEmail, string $userRole, int $eventId, $emailParticipant)
    {
        $emailParticipant = mb_strtolower($emailParticipant);
        $userEmail = mb_strtolower($userEmail);
        $event = $this->eventRepository->get($eventId);
        $this->userRepository->getByEmail($emailParticipant);
        $user = $this->userRepository->getByEmail($emailParticipant);
        $this->addErrorIfParticipantExistOnThisEvent($emailParticipant, $event);
        $this->addErrorIfUserNotExists($user);
        switch ($userRole){
            case AuthorizeMiddleware::LOCAL_ADMIN:{
                return $this->localAdminHasAccessWorkWithParticipant($userEmail, $event);
            }
            case AuthorizeMiddleware::SECRETARY:{
                return $this->secretaryHasAccessWorkWithParticipant($userEmail, $event);
            }
        }

        return false;
    }

    private function teamLeadHasAccessWorkWithParticipantOnTeam(int $teamId, string $email, ?IModel $event)
    {
        $teamLead = $this->teamLeadRepository->getByEmailAndTeamId($email, $teamId);
        if ($teamLead == null){
            $this->response = false;
        }

        if ($event->getStatus() != Event::LEAD_UP){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Данное действие возможно только при статусе мероприятия `'.Event::LEAD_UP.'`'));
        }

        return $this->getResponse();
    }

    public function hasAccessWorkWithTeam(string $role, int $organizationId, int $eventId, string $email)
    {
        $email = mb_strtolower($email);
        $event = $this->eventRepository->get($eventId);
        $organization = $this->organizationRepository->get($organizationId);

        $this->addErrorIfEventNotExists($event);
        $this->addErrorIfOrganizationNotExist($organization);
        $this->addErrorIfEventNotExistOnOrganization($event, $organization);

        switch ($role){
            case AuthorizeMiddleware::LOCAL_ADMIN:{
                return $this->localAdminHasAccessWorkWithTeam($organization, $email);
            }
            case AuthorizeMiddleware::SECRETARY:{
                return $this->secretaryHasAccessWorkWithTeam($event, $email);
            }
        }

        return false;
    }

    public function hasAccessWorkWithTeamWithId(string $userRole, string $userEmail, int $teamId)
    {
        $userEmail = mb_strtolower($userEmail);
        $team = $this->teamRepository->get($teamId);
        $event = $this->eventRepository->get($team->getEventId() ?? -1);
        $organization = $this->organizationRepository->get($event->getIdOrganization() ?? -1);

        $this->addErrorIfTeamNotExists($team);

        switch ($userRole){
            case AuthorizeMiddleware::LOCAL_ADMIN:{
                return $this->localAdminHasAccessWorkWithTeam($organization, $userEmail);
            }
            case AuthorizeMiddleware::SECRETARY:{
                return $this->secretaryHasAccessWorkWithTeam($event, $userEmail);
            }
            case AuthorizeMiddleware::TEAM_LEAD:{
                return $this->teamLeadHasAccessWorkWithTeam($userEmail, $teamId);
            }
        }

        return false;
    }

    private function getResponse()
    {
        if (count($this->errors) == 0){
            return $this->response;
        }

        return $this->getErrorsInJson();
    }

    /**
     * @param LocalAdmin $localAdmin
     * @param Organization $organization
     * @param string $email
     * @return array|bool
     */
    private function localAdminHasAccessWorkWithTeam(?IModel $organization, string $email)
    {
        $email = mb_strtolower($email);
        if ($organization == null){
            return $this->getResponse();
        }
        $this->changeResponseStatusToFalseIfLocalAdminNotExistsInOrganization($email, $organization->getId());
        return $this->getResponse();
    }

    private function secretaryHasAccessWorkWithTeam(?IModel $event, string $email)
    {
        $secretary = $this->secretaryOnOrganizationRepository->getByEmailAndOrgId($email, $event->getIdOrganization());
        if ($secretary == null){
            $this->response = false;
        }

        $email = mb_strtolower($email);
        if ($event == null){
            return $this->getResponse();
        }
        
        $secretaries = $this->secretaryRepository->getFilteredByUserEmail($email);
        $this->changeResponseStatusToFalseIfSecretaryNotExistInEvent($secretaries, $event);
        return $this->getResponse();
    }

    private function addErrorIfOrganizationNotExist(?IModel $organization)
    {
        if ($organization == null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Такой организации не существует'));
        }
    }

    private function addErrorIfEventNotExists(?IModel $event)
    {
        /**@var $event Event*/
        if ($event == null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Такого мероприятия не существует'));
        }
    }

    /**@var $event Event*/
    private function addErrorIfEventNoInLeadUpStatus(?IModel $event)
    {
        if ($event == null){
            return;
        }
        
        if ($event->getStatus() != Event::LEAD_UP){
            $this->addError(new ActionError(ActionError::BAD_STATUS_OF_EVENT, 'Данное действие возможно только при статусе мероприятия `'.Event::LEAD_UP.'`'));
        }
    }

    /**
     * @param Event|null $event
     * @param Organization|null $organization
     */
    private function addErrorIfEventNotExistOnOrganization(?IModel $event, ?IModel $organization)
    {
        if ($event == null || $organization == null){
            return;
        }

        if ($event->getIdOrganization() != $organization->getId()){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Такого мероприятия не существует в рамках данной организации'));
        }
    }

    private function changeResponseStatusToFalseIfLocalAdminNotExistsInOrganization(string $localAdminEmail, int $organizationId)
    {
        if(!$this->localAdminRepository->localAdminIsSetOnDB($localAdminEmail, $organizationId)) {
            $this->response = false;
        }
    }

    public function hasAccessWorkWithParticipant(string $userEmail, int $participantId, string $userRole)
    {
        $userEmail = mb_strtolower($userEmail);
        $participant = $this->eventParticipantRepository->get($participantId);
        $this->addErrorIfParticipantNotExists($participant);
        $event = $this->eventRepository->get($participant->getEventId() ?? -1);
        switch ($userRole){
            case AuthorizeMiddleware::LOCAL_ADMIN:{
                return $this->localAdminHasAccessWorkWithParticipant($userEmail, $event);
            }
            case AuthorizeMiddleware::SECRETARY:{
                return $this->secretaryHasAccessWorkWithParticipant($userEmail, $event);
            }
            case AuthorizeMiddleware::TEAM_LEAD:{
                return $this->teamLeadHasAccessWorkWithParticipantOnTeam($participant->getTeamId(), $userEmail, $event);
            }
        }

        return false;
    }

    /**@var $event Event*/
    private function secretaryHasAccessWorkWithParticipant(string $userEmail, ?IModel $event)
    {
        $userEmail = mb_strtolower($userEmail);
        if ($event == null){
            return $this->getResponse();
        }

        if ($event->getStatus() != Event::LEAD_UP){
            $this->response = false;
        }

        $secretaries = $this->secretaryRepository->getFilteredByEventId($event->getId());
        $secretary = $this->secretaryOnOrganizationRepository->getByEmailAndOrgId($userEmail, $event->getIdOrganization());
        if ($secretary == null){
            $this->response = false;
        }

        foreach ($secretaries as $secretary){
            if ($secretary->getUser()->getEmail() == $userEmail){
                return $this->getResponse();
            }
        }

        $this->response = false;
        return $this->getResponse();
    }

    private function addErrorIfParticipantNotExists(?IModel $participant)
    {
        if ($participant == null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Переданный участник не существует'));
        }
    }

    /**@var $event Event*/
    private function localAdminHasAccessWorkWithParticipant(string $email, ?IModel $event){
        $email = mb_strtolower($email);
        if ($event == null){
            return $this->getResponse();
        }

        $orgId = $event->getIdOrganization();
        if(!$this->localAdminRepository->localAdminIsSetOnDB($email, $orgId)){
            $this->response = false;
        }

        return $this->getResponse();
    }

    public function hasAccessWorkWithEvent(int $eventId, int $organizationId, string $userEmail, $role)
    {
        $userEmail = mb_strtolower($userEmail);
        $event = $this->eventRepository->get($eventId);
        $organization = $this->organizationRepository->get($organizationId);
        $this->addErrorIfEventNoInLeadUpStatus($event);
        $this->addErrorIfEventNotExists($event);
        $this->addErrorIfOrganizationNotExist($organization);
        $this->addErrorIfEventNotExistOnOrganization($event, $organization);

        switch ($role){
            case AuthorizeMiddleware::LOCAL_ADMIN:{
                return $this->localAdminHasAccessWorkWithOrganization($userEmail, $organizationId);
            }
            case AuthorizeMiddleware::SECRETARY:{
                return $this->secretaryHasAccessWorkWithEvent($userEmail, $eventId);
            }
        }
        $this->response = false;
        return $this->getResponse();
    }

    public function hasAccessUpdateResult(string $userRole, string $userEmail, int $resultTrialInEventId)
    {
        $userEmail = mb_strtolower($userEmail);
        $result = $this->resultRepository->get($resultTrialInEventId);
        $this->addErrorIfResultNotFound($result);
        $eventId = $user = $result->getTrialInEvent()->getEventId();
        $event = $this->eventRepository->get($eventId);
        $this->addErrorIfStatusOfEventNotHolding($event);

        switch ($userRole){
            case AuthorizeMiddleware::LOCAL_ADMIN:{
                return $this->localAdminHasAccessWorkWithOrganization($userEmail, $event->getIdOrganization());
            }
            case AuthorizeMiddleware::SECRETARY:{
                return $this->secretaryHasAccessWorkWithEvent($userEmail, $eventId);
            }
        }
        $this->response = false;
        return $this->getResponse();
    }

    public function hasAccessAddTableToEvent(int $eventId, string $userEmail, string $role, int $tableId)
    {
        $organizationId = -1;
        $event = $this->eventRepository->get($eventId);
        if ($event != null){
            $organizationId = $event->getIdOrganization();
        }
        $response = $this->hasAccessWorkWithEvent($eventId, $organizationId, $userEmail, $role);
        $tableInEvent = $this->tableInEventRepository->getFilteredByEventId($eventId);
        $table = $this->tableRepository->get($tableId);
        $this->addErrorIfTableNotExists($table);
        $this->addErrorIfTableExistsOnEvent($tableInEvent);
        if ($response === true){
            return $this->getResponse();
        }

        return $response;
    }

    public function hasAccessAddTrialToEvent(int $eventId, int $trialId, string $userEmail, string $role, $sportObjectId)
    {
        $organizationId = -1;
        $event = $this->eventRepository->get($eventId);
        if ($event != null){
            $organizationId = $event->getIdOrganization();
        }
        $response = $this->hasAccessWorkWithEvent($eventId, $organizationId, $userEmail, $role);

        $trialInEvent = $this->trialInEventRepository->getFilteredByTrialId($trialId, $eventId);
        $this->addErrorIfTrialExistsOnEvent($trialInEvent);
        $sportObject = $this->sportObjectRepository->get($sportObjectId);
        $this->addErrorIfSportObjectNotExists($sportObject);
        //todo проверка на то, что это испытание есть в таблице
        //todo проверка, что вообще есть таблица перевода у мероприятия
        if ($response === true){
            return $this->getResponse();
        }

        return $response;
    }

    public function hasAccessDeleteTrialFromEvent(string $userRole, string $userEmail, int $trialInEventId)
    {
        $trialInEvent = $this->trialInEventRepository->get($trialInEventId);
        if ($trialInEvent == null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Данного испытания нет в мероприятии'));
            return $this->getResponse();
        }

        $organizationId = -1;
        $event = $this->eventRepository->get($trialInEvent->getEventId());
        if ($event != null){
            $organizationId = $event->getIdOrganization();
        }

        $response = $this->hasAccessWorkWithEvent($event->getId(), $organizationId, $userEmail, $userRole);
        if ($response === true){
            return $this->getResponse();
        }

        return $response;
    }

    public function hasAccessAddRefereeToTrialOnEvent(string $role, string $userEmail, int $trialInEventId, int $refereeInOrganizationId)
    {
        $trialInEvent = $this->trialInEventRepository->get($trialInEventId);
        $this->addErrorIfTrialNotExistsOnEvent($trialInEvent);

        $organizationId = -1;
        $event = $this->eventRepository->get($trialInEvent->getEventId());
        if ($event != null){
            $organizationId = $event->getIdOrganization();
        }
        $response = $this->hasAccessWorkWithEvent($event->getId(), $organizationId, $userEmail, $role);
        $referee = $this->refereeOnOrganizationRepository->get($refereeInOrganizationId);
        $this->addErrorIfRefereeNotExistsOnOrganization($referee, $organizationId);
        $refereeInTrialOnEvent = $this->refereeOnTrialInEventRepository->getFilteredByTrialOnEventIdAndUserId($trialInEventId, $referee->getUserId());
        $this->addErrorIfRefereeOnTrialInEventExits($refereeInTrialOnEvent);
        if ($response === true){
            return $this->getResponse();
        }

        return $response;
    }

    public function hasAccessChangeStatusOfEvent(string $userRole, string $userEmail, int $eventId)
    {
        $event = $this->eventRepository->get($eventId);
        $organizationId = -1;
        if ($event != null){
            $organizationId = $event->getIdOrganization();
        }
        $this->hasAccessWorkWithEvent($eventId, $organizationId, $userEmail, $userRole);
        $this->addErrorIfLastStatusOfEvent($event);

        if (count($this->errors) == 1  && $this->errors[0]->getType() == ActionError::BAD_STATUS_OF_EVENT){
            return true;
        }

        if (count($this->errors) != 0) {
            if ($this->errors[0]->getType() == ActionError::BAD_STATUS_OF_EVENT) {
                unset($this->errors[0]);
            }
        }

        return $this->getResponse();
    }

    public function hasAccessAddSecretaryToOrganization(string $userRole, string $localAdminEmail, int $organizationId, $secretaryEmail)
    {
        $organization = $this->organizationRepository->get($organizationId);
        $this->addErrorIfOrganizationNotExist($organization);
        $secretaryOnOrganization = $this->secretaryOnOrganizationRepository->getByEmailAndOrgId($secretaryEmail, $organizationId);
        $this->addErrorIfSecretaryFoundOnOrganization($secretaryOnOrganization);
        $user = $this->userRepository->getByEmail($secretaryEmail);
        $this->addErrorIfUserNotExists($user);
        $this->addErrorIfRoleOfUserNotEqual($user, [AuthorizeMiddleware::SIMPLE_USER, AuthorizeMiddleware::SECRETARY]);
        if ($userRole == AuthorizeMiddleware::LOCAL_ADMIN){
            return $this->localAdminHasAccessWorkWithOrganization($localAdminEmail, $organizationId);
        }

        return false;
    }

    public function hasAccessAddRefereeToOrganization(string $userRole, string $localAdminEmail, int $organizationId, $refereeEmail)
    {
        $organization = $this->organizationRepository->get($organizationId);
        $this->addErrorIfOrganizationNotExist($organization);
        $refereeOnOrganization = $this->refereeOnOrganizationRepository->getByEmailAndOrgId($refereeEmail, $organizationId);
        $this->addErrorIfRefereeExistsOnOrganization($refereeOnOrganization);
        $user = $this->userRepository->getByEmail($refereeEmail);
        $this->addErrorIfUserNotExists($user);
        if ($userRole == AuthorizeMiddleware::LOCAL_ADMIN){
            return $this->localAdminHasAccessWorkWithOrganization($localAdminEmail, $organizationId);
        }

        return false;
    }

    public function hasAccessWorkWithRefereeToOrganization(string $userRole, string $localAdminEmail, int $organizationId, int $refereeId)
    {
        $organization = $this->organizationRepository->get($organizationId);
        $this->addErrorIfOrganizationNotExist($organization);
        $refereeOnOrganization = $this->refereeOnOrganizationRepository->get($refereeId);
        $this->addErrorIfRefereeNotExistsOnOrganization($refereeOnOrganization, $organizationId);

        if ($userRole == AuthorizeMiddleware::LOCAL_ADMIN){
            return $this->localAdminHasAccessWorkWithOrganization($localAdminEmail, $organizationId);
        }

        return false;
    }

    public function hasAccessAddSportObjectToOrganization(string $userRole, string $localAdminEmail, int $organizationId)
    {
        if ($userRole == AuthorizeMiddleware::LOCAL_ADMIN){
            return $this->localAdminHasAccessWorkWithOrganization($localAdminEmail, $organizationId);
        }

        return false;
    }

    public function hasAccessWorkWithSportObject(string $userRole, string $localAdminEmail, int $organizationId, $sportObjectId)
    {
        $sportObject = $this->sportObjectRepository->get($sportObjectId);
        $this->addErrorIfSportObjectNotExists($sportObject);
        $this->addErrorIfSportObjectNotExistsOnOrganization($sportObject, $organizationId);
        if ($userRole == AuthorizeMiddleware::LOCAL_ADMIN){
            return $this->localAdminHasAccessWorkWithOrganization($localAdminEmail, $organizationId);
        }

        return false;
    }

    public function hasAccessDeleteSecretaryFromOrganization(string $userRole, string $localAdminEmail, int $organizationId, int $secretaryId)
    {
        $organization = $this->organizationRepository->get($organizationId);
        $this->addErrorIfOrganizationNotExist($organization);
        $secretary = $this->secretaryOnOrganizationRepository->get($secretaryId);
        $this->addErrorIfSecretaryNotFoundOnOrganization($secretary, $organizationId);
        if ($userRole == AuthorizeMiddleware::LOCAL_ADMIN){
            return $this->localAdminHasAccessWorkWithOrganization($localAdminEmail, $organizationId);
        }

        return false;
    }

    private function addErrorIfRoleOfUserNotEqual(?User $user, array $roles){
        if ($user == null){
            return;
        }

        $nameOfRole = $this->rolRepository->get($user->getRoleId())->getName();
        if (!(in_array($nameOfRole, $roles))){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Этот пользователь уже имеет другую роль'));
        }
    }

    public function hasAccessDeleteRefereeFromTrialOnEvent(string $role, string $email, int $refereeInTrialOnEventId)
    {
        /**@var $refereeInTrialOnEvent RefereeOnTrialInEvent*/
        $refereeInTrialOnEvent = $this->refereeOnTrialInEventRepository->get($refereeInTrialOnEventId);
        $this->addErrorIfRefereeOnTrialInEventNotExists($refereeInTrialOnEvent);
        if ($refereeInTrialOnEvent == null){
            return $this->getResponse();
        }

        $trialInEvent = $this->trialInEventRepository->get($refereeInTrialOnEvent->getTrialInEventId());
        $event = $this->eventRepository->get($trialInEvent->getEventId());
        $response = $this->hasAccessWorkWithEvent($event->getId(), $event->getIdOrganization(), $email, $role);

        if ($response === true){
            return $this->getResponse();
        }

        return $response;
    }

    private function changeResponseStatusToFalseIfSecretaryNotExistInOrganization(?Secretary $secretary, ?Organization $organization)
    {
        if ($organization == null){
            return;
        }

        if ($secretary == null){
            $this->response = false;
            return;
        }

        if ($organization->getId() != $secretary->getOrganizationId()){
            $this->response = false;
        }
    }

    /**
     * @param Secretary[]|null $secretaries
     * @param Event|null $event
     */
    private function changeResponseStatusToFalseIfSecretaryNotExistInEvent(?array $secretaries, ?IModel $event)
    {
        if ($secretaries == null){
            $this->response = false;
            return;
        }

        if ($event == null){
            return;
        }

        foreach ($secretaries as $secretary){
            if ($secretary->getEventId() == $event->getId()){
                return;
            }
        }

        $this->response = false;
    }

    /**@var $participant EventParticipant*/
    private function addErrorIfOnEventNotExistThisParticipant(?IModel $event, ?IModel $participant)
    {
        if ($event == null || $participant == null){
            return;
        }

        if ($event->getId() !== $participant->getEventId()){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Переданный участник не относится к данному мероприятию'));
        }
    }

    private function addErrorIfParticipantExistOnThisEvent(string $emailParticipant, ?IModel $event)
    {
        if ($event == null){
            return;
        }

        if ($this->eventParticipantRepository->getByEmailAndEvent($emailParticipant, $event->getId()) != null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Такой участник уже есть в мероприятии'));
        }
    }

    private function addErrorIfTeamNotExists(?IModel $team)
    {
        if ($team == null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Такой команды не существует'));
        }
    }

    private function localAdminHasAccessWorkWithOrganization(string $userEmail, int $organizationId)
    {
        $userEmail = mb_strtolower($userEmail);
        $organizationIdOfLocalAdmin = $this->localAdminRepository->getOrganizationIdFilteredByEmail($userEmail);
        if ($organizationId != $organizationIdOfLocalAdmin){
            $this->response = false;
        }

        return $this->getResponse();
    }

    private function secretaryHasAccessWorkWithEvent(string $userEmail, int $eventId)
    {
        $userEmail = mb_strtolower($userEmail);
        $secretaries = $this->secretaryRepository->getFilteredByEventId($eventId);
        $event = $this->eventRepository->get($eventId);
        $secretary = $this->secretaryOnOrganizationRepository->getByEmailAndOrgId($userEmail, $event->getIdOrganization());
        if ($secretary == null){
            $this->response = false;
        }
        if ($secretaries != null) {
            foreach ($secretaries as $secretary) {
                if ($secretary->getUser()->getEmail() == $userEmail) {
                    return $this->getResponse();
                }
            }
        }

        $this->response = false;

        return $this->getResponse();
    }

    private function addErrorIfUserNotExists(?User $user)
    {
        if ($user == null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Такого пользователя не существует'));
        }
    }

    private function teamLeadHasAccessWorkWithTeam(string $userEmail, int $teamId)
    {
        if($this->teamLeadRepository->getByEmailAndTeamId($userEmail, $teamId) == null){
            $this->response = false;
        }

        return $this->getResponse();
    }

    private function addErrorIfSecretaryFoundOnOrganization(?SecretaryOnOrganization $secretaryOnOrganization)
    {
        if ($secretaryOnOrganization !== null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Такой секретарь уже есть в справочнике этой организации'));
        }
    }


    /**@var $secretary SecretaryOnOrganization*/
    private function addErrorIfSecretaryNotFoundOnOrganization(?IModel $secretary, int $organizationId)
    {
        if ($secretary == null){
            return;
        }

        if ($secretary->getOrganizationId() !== $organizationId){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Данный секретарь не относится к этой организации'));
        }
    }

    private function addErrorIfSportObjectNotExists(?IModel $sportObject)
    {
        if ($sportObject == null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Данного спортивного объекта не существует'));
        }
    }

    private function addErrorIfSportObjectNotExistsOnOrganization(?IModel $sportObject, $organizationId)
    {
        if ($sportObject == null){
            return;
        }

        if ($sportObject->getOrganizationId() !== $organizationId){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Данный спортивный объект не относится к этой организации'));
        }
    }

    private function addErrorIfRefereeExistsOnOrganization(?RefereeOnOrganization $refereeOnOrganization)
    {
        if ($refereeOnOrganization != null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Судья с такой почтой уже существует в справочнике организации'));
        }
    }

    /**@var $refereeOnOrganization RefereeOnOrganization*/
    private function addErrorIfRefereeNotExistsOnOrganization(?IModel $refereeOnOrganization, int $organizationId)
    {
        if ($refereeOnOrganization == null){
            return;
        }

        if ($refereeOnOrganization->getOrganizationId() !== $organizationId){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Данный судья не относится к этой организации'));
        }
    }

    private function addErrorIfTableExistsOnEvent(?IModel $tableInEvent)
    {
        if ($tableInEvent !== null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'У этого мероприятия уже выбрана таблица перевода'));
        }
    }

    private function addErrorIfTableNotExists(?IModel $table)
    {
        if ($table == null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Такой таблицы не существует'));
        }
    }

    private function addErrorIfTrialExistsOnEvent($trialInEvent)
    {
        if ($trialInEvent != null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Такое испытание уже добавлено в данное мероприятие'));
        }
    }

    private function addErrorIfTrialNotExistsOnEvent(?IModel $trialInEvent)
    {
        if ($trialInEvent == null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Данного испытания нет в мероприятии'));
        }
    }

    private function addErrorIfRefereeOnTrialInEventExits(?IModel $refereeInTrialOnEvent)
    {
        if ($refereeInTrialOnEvent != null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Этот судья уже добавлен в данное испытание'));
        }
    }

    private function addErrorIfRefereeOnTrialInEventNotExists(?IModel $refereeInTrialOnEvent)
    {
        if ($refereeInTrialOnEvent == null){
            $this->addError(new ActionError(ActionError::class, 'Данной судьи не существует'));
        }
    }

    private function addErrorIfLastStatusOfEvent(?IModel $event)
    {
        if ($event == null){
            return;
        }

        if ($event->getStatus() == Event::COMPLETED){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Это мероприятие уже завершено'));
        }
    }

    private function addErrorIfResultNotFound(?IModel $result)
    {
        if ($result == null){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Такого поля результата не найдено'));
        }
    }

    private function addErrorIfStatusOfEventNotHolding(?IModel $event)
    {
        if ($event == null){
            return;
        }

        if ($event->getStatus() != Event::HOLDING){
            $this->addError(new ActionError(ActionError::BAD_REQUEST, 'Данная операция доступна только в статусе мероприятия "проведение"'));
        }
    }
}