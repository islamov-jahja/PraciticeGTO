<?php

namespace App\Services\Referee;
use App\Domain\Models\Referee\RefereeOnOrganization;
use App\Domain\Models\Referee\RefereeOnTrialInEvent;
use App\Persistance\Repositories\Event\EventRepository;
use App\Persistance\Repositories\Referee\RefereeInTrialOnEventRepository;
use App\Persistance\Repositories\Referee\RefereeRepository;
use App\Persistance\Repositories\TrialRepository\TrialInEventRepository;
use App\Persistance\Repositories\User\UserRepository;
use App\Services\EmailSendler\EmailSendler;

class RefereeService
{
    private $refereeRepository;
    private $userRepository;
    private $refereeOnTrialInEventRepository;
    private $eventRepository;
    private $trialInEventRepository;
    private $emailSender;

    public function __construct(RefereeRepository $refereeRepository, UserRepository $userRepository, RefereeInTrialOnEventRepository $refereeOnTrialInEventRepository, EventRepository $eventRepository, EmailSendler $emailSendler, TrialInEventRepository $trialInEventRepository)
    {
        $this->refereeRepository = $refereeRepository;
        $this->userRepository = $userRepository;
        $this->refereeOnTrialInEventRepository = $refereeOnTrialInEventRepository;
        $this->trialInEventRepository = $trialInEventRepository;
        $this->emailSender = $emailSendler;
        $this->eventRepository = $eventRepository;
    }

    public function addToOrganization(int $organizationId, $refereeEmail)
    {
        $user = $this->userRepository->getByEmail($refereeEmail);
        $refereeOnOrganization = new RefereeOnOrganization(-1, $organizationId, $user->getId(), $user);
        return $this->refereeRepository->add($refereeOnOrganization);
    }

    public function get(int $organizationId)
    {
        return $this->refereeRepository->getFilteredByOrgId($organizationId);
    }

    public function delete(int $refereeId)
    {
        $this->refereeRepository->delete($refereeId);
    }

    public function addToTrialOnEvent(int $refereeInOrganizationId, int $trialInEventId)
    {
        $refereeInOrganization = $this->refereeRepository->get($refereeInOrganizationId);
        $refereeInTrialOnEvent = new RefereeOnTrialInEvent(-1, $trialInEventId, $refereeInOrganization->getUser());

        $message = EmailSendler::$MESSAGE_FOR_REFEREE_ON_ADDING_TRIAL_IN_EVENT;
        $trialInEvent = $this->trialInEventRepository->get($trialInEventId);
        $event = $this->eventRepository->get($trialInEvent->getEventId());
        $message = str_replace('event_name', $event->getName(), $message);
        $message = str_replace('trial_name', $trialInEvent->getTrial()->getName(), $message);
        $message = str_replace('date_time', $trialInEvent->getStartDate(), $message);
        $message = str_replace('place', $trialInEvent->getSportObject()->getName(), $message);
        $message = str_replace('address', $trialInEvent->getSportObject()->getAddress(), $message);
        $this->emailSender->sendMessage([$refereeInTrialOnEvent->getUser()->getEmail()], $message);
        return $this->refereeOnTrialInEventRepository->add($refereeInTrialOnEvent);
    }

    public function deleteRefereeFromTrialOnEvent(int $refereeInTrialOnEventId)
    {
        $referee = $this->refereeOnTrialInEventRepository->get($refereeInTrialOnEventId);
        $this->refereeOnTrialInEventRepository->delete($refereeInTrialOnEventId);

        if ($referee != null){
            $message = EmailSendler::$MESSAGE_FOR_REFEREE_ON_DELETING_FROM_TRIAL_IN_EVENT;
            $trialInEvent = $this->trialInEventRepository->get($referee->getTrialInEventId());
            $event = $this->eventRepository->get($trialInEvent->getEventId());
            $message = str_replace('trial_name', $trialInEvent->getTrial()->getName(), $message);
            $message = str_replace('event_name', $event->getName(), $message);
            $this->emailSender->sendMessage([$referee->getUser()->getEmail()], $message);
        }
    }
}