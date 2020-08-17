<?php

namespace App\Services\EventParticipant;
use App\Domain\Models\EventParticipant\EventParticipant;
use App\Domain\Models\User\UserCreater;
use App\Persistance\Repositories\Event\EventRepository;
use App\Persistance\Repositories\EventParticipant\EventParticipantRepository;
use App\Persistance\Repositories\Team\TeamRepository;
use App\Persistance\Repositories\User\UserRepository;
use App\Services\EmailSendler\EmailSendler;
use Symfony\Component\Validator\Constraints\DateTime;

class EventParticipantService
{
    private $eventParticipantRepository;
    private $userRepository;
    private $teamRepository;
    private $eventRepository;
    private $emailSender;

    public function __construct(EventParticipantRepository $eventParticipantRepository, UserRepository $userRepository, TeamRepository $teamRepository, EmailSendler $emailSendler, EventRepository $eventRepository)
    {
        $this->eventParticipantRepository = $eventParticipantRepository;
        $this->userRepository = $userRepository;
        $this->teamRepository = $teamRepository;
        $this->emailSender = $emailSendler;
        $this->eventRepository = $eventRepository;
    }

    /**@return EventParticipant[]*/
    public function getAllForEvent(int $eventId):array
    {
        return $this->eventParticipantRepository->getAllByEventId($eventId);
    }

    public function confirmApply(int $participantId)
    {
        /**@var $participant EventParticipant*/
        $participant = $this->eventParticipantRepository->get($participantId);
        $participant->doConfirm();
        $this->eventParticipantRepository->update($participant);

        $message = EmailSendler::$MESSAGE_FOR_PARTICIPANT_ON_CONFIRM;
        $event = $this->eventRepository->get($participant->getEventId());
        $message = str_replace('event_name', $event->getName(), $message);
        $this->emailSender->sendMessage([$participant->getUser()->getEmail()], $message);
    }

    public function delete(int $participantId)
    {
        $participant = $this->eventParticipantRepository->get($participantId);
        $this->eventParticipantRepository->delete($participantId);

        if ($participant != null) {
            $event = $this->eventRepository->get($participant->getEventId());
            $message = EmailSendler::$MESSAGE_ON_DELETE_FROM_EVENT_FOR_PARTICIPANT;
            $message = str_replace('event_name', $event->getName(), $message);
            $this->emailSender->sendMessage([$participant->getUser()->getEmail()], $message);
        }
    }

    public function addToTeam(string $userEmail, bool $confirmed, $teamId)
    {
        $team = $this->teamRepository->get($teamId);
        $user = $this->userRepository->getByEmail($userEmail);
        if ($user == null){
            return -1;
        }

        $eventParticipant = new EventParticipant(-1, $team->getEventId(), $user->getId(), $confirmed, $user, $teamId);

        $message = EmailSendler::$MESSAGE_FOR_PARTICIPANT_ON_ADDING_TO_EVENT_TO_TEAM;
        $team = $this->teamRepository->get($teamId);
        $message = str_replace('team_name', $team->getName(), $message);
        $event = $this->eventRepository->get($team->getEventId());
        $message = str_replace('event_name', $event->getName(), $message);
        $this->emailSender->sendMessage([$userEmail], $message);

        return $this->eventParticipantRepository->add($eventParticipant);
    }

    public function getAllForTeam(int $teamId)
    {
        return $this->eventParticipantRepository->getAllFilteredByTeamId($teamId);
    }

    public function addToEvent($emailOfUserToAdd, bool $false, int $eventId)
    {
        $user = $this->userRepository->getByEmail($emailOfUserToAdd);
        if ($user == null){
            return -1;
        }

        $eventParticipant = new EventParticipant(-1, $eventId, $user->getId(), false, $user, null);

        $message = EmailSendler::$MESSAGE_FOR_PARTICIPANT_ON_ADDING_TO_EVENT;
        $event = $this->eventRepository->get($eventId);
        $message = str_replace('event_name', $event->getName(), $message);
        $this->emailSender->sendMessage([$emailOfUserToAdd], $message);
        return $this->eventParticipantRepository->add($eventParticipant);
    }

    public function updateUserOnTeam(string $name, int $gender, \DateTime $dateOfBirth, int $participantId, string $uid)
    {
        $participant = $this->eventParticipantRepository->get($participantId);
        $user = $this->userRepository->getByEmail($participant->getUser()->getEmail());
        $updatedUser = UserCreater::createModel([
            'id' => $user->getId(),
            'name' => $name,
            'password' => $user->getPassword(),
            'email' => $user->getEmail(),
            'roleId' => $user->getRoleId(),
            'isActivity' => $user->isActivity(),
            'dateTime' => new \DateTime($user->getRegistrationDate()),
            'gender' => $gender,
            'dateOfBirth' => $dateOfBirth,
        ]);
        $updatedUser->setUid($uid);
        $this->userRepository->update($updatedUser);
    }

}