<?php

namespace App\Services\Team;
use App\Application\Middleware\AuthorizeMiddleware;
use App\Domain\Models\Team\Team;
use App\Persistance\Repositories\Event\EventRepository;
use App\Persistance\Repositories\EventParticipant\EventParticipantRepository;
use App\Persistance\Repositories\LocalAdmin\LocalAdminRepository;
use App\Persistance\Repositories\Role\RoleRepository;
use App\Persistance\Repositories\Team\TeamRepository;
use App\Persistance\Repositories\TeamLead\TeamLeadRepository;
use App\Persistance\Repositories\User\UserRepository;
use App\Services\EmailSendler\EmailSendler;

class TeamService
{
    private $userRepository;
    private $teamRepository;
    private $teamLeadRepository;
    private $eventRepository;
    private $localAdminRepository;
    private $roleRepository;
    private $eventParticipantRepository;
    private $emailSender;

    public function __construct(UserRepository $userRepository, TeamRepository $teamRepository, EventRepository $eventRepository, LocalAdminRepository $localAdminRepository, TeamLeadRepository $teamLeadRepository, RoleRepository $roleRepository, EventParticipantRepository $eventParticipantRepository, EmailSendler $emailSendler)
    {
        $this->teamRepository = $teamRepository;
        $this->userRepository = $userRepository;
        $this->eventRepository = $eventRepository;
        $this->localAdminRepository = $localAdminRepository;
        $this->teamLeadRepository = $teamLeadRepository;
        $this->roleRepository = $roleRepository;
        $this->eventParticipantRepository = $eventParticipantRepository;
        $this->emailSender = $emailSendler;
    }

    public function add($name, int $eventId)
    {
        $team = new Team(-1, $eventId, $name);
        return $this->teamRepository->add($team);
    }

    /**
     * @param int $eventId
     * @param int $organizationId
     * @return Team[]|array
     */
    public function getAll(int $eventId, int $organizationId)
    {
        return $this->teamRepository->getAllFilteredByEventIdOrgId($eventId, $organizationId);
    }

    /**@return Team[]*/
    public function getListForUser(string $email, string $role):array
    {
        $user = $this->userRepository->getByEmail($email);
        $teams = [];
        switch ($role){
            case AuthorizeMiddleware::TEAM_LEAD:{
                $teams = $this->teamRepository->getAllForTeamLeadWithUserId($user->getId());
                break;
            }
            case AuthorizeMiddleware::SECRETARY:{
                $teams = $this->teamRepository->getAllForSecretaryWithUserId($user->getId());
                break;
            }
            case AuthorizeMiddleware::LOCAL_ADMIN:{
                $organizationId = $this->localAdminRepository->getOrganizationIdFilteredByEmail($user->getEmail());
                $teams = $this->teamRepository->getAllForOrganizationId($organizationId);
                break;
            }
        }

        $response = [];
        foreach ($teams as $team){
            $teamArray = $team->toArray();
            $event = $this->eventRepository->get($team->getEventId());
            $teamArray['organizationId'] = $event->getIdOrganization();
            $teamArray['nameOfEvent'] = $event->getName();
            $response[] = $teamArray;
        }

        return $response;
    }

    public function confirm(int $teamId)
    {
        $this->teamRepository->confirm($teamId);

        $message = EmailSendler::$MESSAGE_FOR_PARTICIPANT_ON_CONFIRM_TEAM;
        $team = $this->teamRepository->get($teamId);
        $event = $this->eventRepository->get($team->getEventId());
        $message = str_replace('team_name', $team->getName(), $message);
        $message = str_replace('event_name', $event->getName(), $message);
        $participants = $this->eventParticipantRepository->getAllFilteredByTeamId($teamId);
        $users = [];

        foreach ($participants as $participant){
            $users[] = $participant->getUser()->getEmail();
        }

        $this->emailSender->sendMessage($users, $message);
    }

    public function get(int $teamId)
    {
        $team = $this->teamRepository->get($teamId);
        if ($team == null){
            return $team;
        }

        $event = $this->eventRepository->get($team->getEventId());
        $teamInArray = $team->toArray();
        $teamInArray['organizationId'] = $event->getIdOrganization();
        return $teamInArray;
    }

    public function update(string $name, int $teamId)
    {
        $team = $this->teamRepository->get($teamId);
        $newTeam = new Team($team->getId(), $team->getEventId(), $name, $team->getCountOfPlayers());
        $this->teamRepository->update($newTeam);
    }

    public function delete(int $teamId)
    {
        $this->deleteAllTeamLeadsFromTeam($teamId);
        $this->deleteAllEventParticipantsOnTeam($teamId);
        $this->teamRepository->delete($teamId);
    }

    private function deleteAllTeamLeadsFromTeam(int $teamId)
    {
        $teamLeads = $this->teamLeadRepository->getByTeamId($teamId);
        $teamLeadsAll = $this->teamLeadRepository->getAll();
        $simpleUserRoleId = $this->roleRepository->getByName(AuthorizeMiddleware::SIMPLE_USER)->getId();
        foreach ($teamLeads as $teamLead){
            if (!$this->findTeamLeadOnOtherTeam($teamLead->getUserId(), $teamId, $teamLeadsAll)){
                $user = $teamLead->getUser();
                $user->setRoleId($simpleUserRoleId);
                $this->userRepository->update($user);
            }

            $this->teamLeadRepository->delete($teamLead->getTeamLeadId());
        }
    }

    private function findTeamLeadOnOtherTeam($userId, $teamId, $teamLeadsAll)
    {
        foreach ($teamLeadsAll as $teamLead){
            if ($teamLead->getUserId() == $userId && $teamLead->getTeamId() != $teamId){
                return true;
            }
        }

        return false;
    }

    private function deleteAllEventParticipantsOnTeam(int $teamId)
    {
        $eventParticipants = $this->eventParticipantRepository->getAllFilteredByTeamId($teamId);
        foreach ($eventParticipants as $eventParticipant){
            $this->eventParticipantRepository->delete($eventParticipant->getEventParticipantId());
        }
    }
}