<?php

namespace App\Services\TeamLead;
use App\Application\Middleware\AuthorizeMiddleware;
use App\Domain\Models\TeamLead\TeamLead;
use App\Persistance\Repositories\Event\EventRepository;
use App\Persistance\Repositories\Role\RoleRepository;
use App\Persistance\Repositories\Team\TeamRepository;
use App\Persistance\Repositories\TeamLead\TeamLeadRepository;
use App\Persistance\Repositories\User\UserRepository;
use App\Services\EmailSendler\EmailSendler;

class TeamLeadService
{
    private $teamLeadRepository;
    private $userRepository;
    private $roleRepository;
    private $teamRepository;
    private $emailSender;
    private $eventRepository;

    public function __construct(TeamLeadRepository $teamLeadRepository, UserRepository $userRepository, RoleRepository $roleRepository, TeamRepository $teamRepository, EventRepository $eventRepository, EmailSendler $emailSendler)
    {
        $this->teamLeadRepository = $teamLeadRepository;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->teamRepository = $teamRepository;
        $this->eventRepository = $eventRepository;
        $this->emailSender = $emailSendler;
    }

    /**@return TeamLead[]*/
    public function getAllForTeam(int $teamId):array
    {
        return $this->teamLeadRepository->getByTeamId($teamId);
    }

    public function add($email, int $teamId)
    {
        $user = $this->userRepository->getByEmail($email);
        if ($user == null){
            return -3;
        }
        $nameOfRuleOfUser = $this->roleRepository->get($user->getRoleId())->getName();
        if (!in_array($nameOfRuleOfUser, [AuthorizeMiddleware::SIMPLE_USER, AuthorizeMiddleware::TEAM_LEAD])){
            return -1;
        }

        $teamLead = $this->teamLeadRepository->getByEmailAndTeamId($email, $teamId);
        if ($teamLead != null){
            return -2;
        }

        $user->setRoleId($this->roleRepository->getByName(AuthorizeMiddleware::TEAM_LEAD)->getId());
        $this->userRepository->update($user);
        $teamLead = new TeamLead(-1, $teamId, $user->getId(), $user);

        $message = EmailSendler::$MESSAGE_FOR_TEAMLEAD_ON_ADDING;
        $team = $this->teamRepository->get($teamId);
        $event = $this->eventRepository->get($team->getEventId());
        $message = str_replace('team_name', $team->getName(), $message);
        $message = str_replace('event_name', $event->getName(), $message);
        $this->emailSender->sendMessage([$email], $message);
        return $this->teamLeadRepository->add($teamLead);
    }

    public function get(int $teamLeadId)
    {
        return $this->teamLeadRepository->get($teamLeadId);
    }

    public function delete(int $teamLeadId)
    {
        $teamLead = $this->teamLeadRepository->get($teamLeadId);
        $teams = $this->teamRepository->getAllForTeamLeadWithUserId($teamLead->getUserId());
        if (count($teams) == 1){
            $user = $teamLead->getUser();
            $user->setRoleId($this->roleRepository->getByName(AuthorizeMiddleware::SIMPLE_USER)->getId());
            $this->userRepository->update($user);
        }

        $message = EmailSendler::$MESSAGE_FOR_DELETING_TEAM_LEAD;
        $team = $this->teamRepository->get($teamLead->getTeamId());
        $event = $this->eventRepository->get($team->getEventId());
        $message = str_replace('event_name', $event->getName(), $message);
        $message = str_replace('team_name', $team->getName(), $message);
        $this->emailSender->sendMessage([$teamLead->getUser()->getEmail()], $message);
        $this->teamLeadRepository->delete($teamLeadId);
    }
}