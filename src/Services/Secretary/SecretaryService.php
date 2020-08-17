<?php

namespace App\Services\Secretary;

use App\Application\Actions\ActionError;
use App\Application\Middleware\AuthorizeMiddleware;
use App\Domain\Models\Event\Event;
use App\Domain\Models\Secretary\Secretary;
use App\Domain\Models\Secretary\SecretaryOnOrganization;
use App\Domain\Models\User\User;
use App\Domain\Models\User\UserCreater;
use App\Persistance\Repositories\Event\EventRepository;
use App\Persistance\Repositories\LocalAdmin\LocalAdminRepository;
use App\Persistance\Repositories\Organization\OrganizationRepository;
use App\Persistance\Repositories\Role\RoleRepository;
use App\Persistance\Repositories\Secretary\SecretaryOnOrganizationRepository;
use App\Persistance\Repositories\Secretary\SecretaryRepository;
use App\Persistance\Repositories\User\UserRepository;
use App\Services\EmailSendler\EmailSendler;
use App\Services\Token\Token;
use DateTime;
use PharIo\Manifest\Email;
use Psr\Http\Message\ResponseInterface;

class SecretaryService
{
    private $secretaryRepository;
    private $userRepository;
    private $organizationRepository;
    private $localAdminRepository;
    private $eventRepository;
    private $roleRepository;
    private $secretaryOnOrganizationRepository;
    private $emailSender;

    public function __construct(
        SecretaryRepository $secretaryRepository,
        UserRepository $userRepository,
        OrganizationRepository $organizationRepository,
        LocalAdminRepository $localAdminRepository,
        EventRepository $eventRepository,
        RoleRepository $roleRepository,
        SecretaryOnOrganizationRepository $secretaryOnOrganizationRepository,
        EmailSendler $emailSendler
    )
    {
        $this->secretaryRepository = $secretaryRepository;
        $this->userRepository = $userRepository;
        $this->organizationRepository = $organizationRepository;
        $this->localAdminRepository = $localAdminRepository;
        $this->eventRepository = $eventRepository;
        $this->roleRepository = $roleRepository;
        $this->secretaryOnOrganizationRepository = $secretaryOnOrganizationRepository;
        $this->emailSender = $emailSendler;
    }

    public function addToEvent($localAdminEmail, int $secretaryId, int $organizationId, int $eventId, ResponseInterface $response)
    {
        $secretary = $this->secretaryOnOrganizationRepository->get($secretaryId);
        if ($secretary == null){
            $error = new ActionError(ActionError::BAD_REQUEST, 'Такого секретаря в организации не существует');
            $response->getBody()->write(json_encode(['errors' => array($error->jsonSerialize())]));
            return $response->withStatus(400);
        }

        if ($secretary->getOrganizationId() !== $organizationId){
            $error = new ActionError(ActionError::BAD_REQUEST, 'Такого секретаря в организации не существует');
            $response->getBody()->write(json_encode(['errors' => array($error->jsonSerialize())]));
            return $response->withStatus(400);
        }

        $secretaryEmail = $secretary->getUser()->getEmail();
        $response = $this->getInitedResponseWitStatus($organizationId, $localAdminEmail, $eventId, $response);
        if ($response->getStatusCode() != 200){
            return $response;
        }

        $user = $this->userRepository->getByEmail($secretaryEmail);

        if ($user == null) {
            $error = new ActionError(ActionError::BAD_REQUEST, 'Такого пользователя не существует');
            $response->getBody()->write(json_encode(['errors' => array($error->jsonSerialize())]));
            return $response->withStatus(404);
        }else{
            if ($this->secretaryIsSetOnThisEvent($user, $eventId)){
                $error = new ActionError(ActionError::BAD_REQUEST, 'Этот пользователь уже является секретарем в данном мероприятии');
                $response->getBody()->write(json_encode(['errors' => array($error->jsonSerialize())]));
                return $response->withStatus(400);
            }
        }

        $message = EmailSendler::$MESSAGE_FOR_SECRETARY_ABOUT_ADDING_HIM;
        $event = $this->eventRepository->get($eventId);
        $message = str_replace('event_name', $event->getName(), $message);
        $this->emailSender->sendMessage([$user->getEmail()], $message);
        return $this->secretaryRepository->add(new Secretary(-1 , $eventId, $organizationId, $user));
    }

    private function secretaryIsSetOnThisEvent(User $user, int $evenId):bool
    {
        $secretaries = $this->secretaryRepository->getFilteredByUserEmail($user->getEmail());

        if ($secretaries == null){
            return false;
        }

        foreach ($secretaries as $secretary){
            if ($secretary->getEventId() == $evenId){
                return true;
            }
        }

        return false;
    }

    private function secretaryInOtherOrganization(User $user, $organizationId):bool
    {
        if ($user->getRoleId() != $this->roleRepository->getByName(AuthorizeMiddleware::SECRETARY)->getId()) {
            return false;
        }

        $secretaries = $this->secretaryRepository->getFilteredByUserEmail($user->getEmail());

        foreach ($secretaries as $secretary){
            if ($secretary->getOrganizationId() != $organizationId){
                return true;
            }
        }

        return false;
    }

    public function get(int $organizationId, int $eventId, string $localAdminEmail, ResponseInterface $response)
    {
        $response = $this->getInitedResponseWitStatus($organizationId, $localAdminEmail, $eventId, $response);
        if ($response->getStatusCode() != 200){
            return $response;
        }

        return $secretaries = $this->secretaryRepository->getFilteredByEventId($eventId);
    }

    private function getRoleIdWithName(string $name, array $roles):?int
    {
        foreach ($roles as $role){
            if ($role['name_of_role'] == $name){
                return  $role['role_id'];
            }
        }

        return null;
    }

    public function delete(int $organizationId, int $eventId, int $secretaryId, string $localAdminEmail, ResponseInterface $response)
    {
        $response = $this->getInitedResponseWitStatus($organizationId, $localAdminEmail, $eventId, $response);
        if ($response->getStatusCode() != 200){
            return $response;
        }

        $secretary = $this->secretaryRepository->get($secretaryId);
        if ($secretary == null){
            $error = new ActionError(ActionError::BAD_REQUEST, 'Данного секретаря не существует');
            $response->getBody()->write(json_encode(['errors' => array($error->jsonSerialize())]));
            return $response->withStatus(400);
        }

        if ($secretary->getEventId() != $eventId){
            $error = new ActionError(ActionError::BAD_REQUEST, 'Данный секретарь не относится к этому мероприятию');
            $response->getBody()->write(json_encode(['errors' => array($error->jsonSerialize())]));
            return $response->withStatus(400);
        }

        $message = EmailSendler::$MESSAGE_FOR_SECRETARY_ABOUT_DELETE_HIM;
        $event = $this->eventRepository->get($eventId);
        $message = str_replace('event_name', $event->getName(), $message);
        $this->emailSender->sendMessage([$secretary->getUser()->getEmail()], $message);
        $this->secretaryRepository->delete($secretaryId);
    }

    public function add(int $eventId, int $organizationId, string $name, string $password,  DateTime $dateOfBirth, string $email, int $gender, $localAdminEmail, ResponseInterface $response)
    {
        $response = $this->getInitedResponseWitStatus($organizationId, $localAdminEmail, $eventId, $response);
        if ($response->getStatusCode() != 200){
            return $response;
        }

        $user = $this->userRepository->getByEmail($email);
        $roles = $this->roleRepository->getAll();
        $roleId = $this->getRoleIdWithName(AuthorizeMiddleware::SECRETARY, $roles);

        if ($user == null) {
            $user = UserCreater::createModel([
                'id' => -1,
                'name' => $name,
                'password' => Token::getEncodedPassword($password),
                'email' => $email,
                'roleId' => $roleId,
                'isActivity' => 1,
                'dateTime' => new DateTime(),
                'dateOfBirth' => $dateOfBirth,
                'gender' => $gender
            ]);

            $userId = $this->userRepository->add($user);
            $user->setId($userId);
        }else{
            $error = new ActionError(ActionError::BAD_REQUEST, 'Такой пользователь уже существует');
            $response->getBody()->write(json_encode(['errors' => array($error->jsonSerialize())]));
            return $response->withStatus(400);
        }

        $secretary = new Secretary(-1, $eventId, $organizationId, $user);
        return $this->secretaryRepository->add($secretary);
    }

    private function getInitedResponseWitStatus(int $organizationId, string $localAdminEmail, int $eventId, ResponseInterface $response)
    {
        if (!$this->localAdminRepository->localAdminIsSetOnDB($localAdminEmail, $organizationId)) {
            $error = new ActionError(ActionError::BAD_REQUEST, 'Данный локальный администратор не может работать с мероприятиями этой организации');
            $response->getBody()->write(json_encode(['errors' => array($error->jsonSerialize())]));
            return $response->withStatus(403);
        }

        if ($this->organizationRepository->get($organizationId) == null) {
            $error = new ActionError(ActionError::BAD_REQUEST, 'Такой организации не существует');
            $response->getBody()->write(json_encode(['errors' => array($error->jsonSerialize())]));
            return $response->withStatus(400);
        }

        /**@var $event Event*/
        $event = $this->eventRepository->get($eventId);
        if ($event == null){
            $error = new ActionError(ActionError::BAD_REQUEST, 'Данного мероприятия не существует');
            $response->getBody()->write(json_encode(['errors' => array($error->jsonSerialize())]));
            return $response->withStatus(400);
        }

        if ($event->getIdOrganization() != $organizationId){
            $error = new ActionError(ActionError::BAD_REQUEST, 'Мероприятие не относится к данной организации');
            $response->getBody()->write(json_encode(['errors' => array($error->jsonSerialize())]));
            return $response->withStatus(403);
        }

        return $response;
    }

    public function addToOrganization(int $organizationId, $secretaryEmail)
    {
        $user = $this->userRepository->getByEmail($secretaryEmail);
        $secretaryOnOrganization = new SecretaryOnOrganization(-1, $user->getId(), $organizationId, $user);
        $user = $this->userRepository->getByEmail($secretaryEmail);
        $roles = $this->roleRepository->getAll();
        $roleId = $this->getRoleIdWithName(AuthorizeMiddleware::SECRETARY, $roles);
        $user->setRoleId($roleId);
        $this->userRepository->update($user);
        return $this->secretaryOnOrganizationRepository->add($secretaryOnOrganization);
    }

    public function deleteFromOrganization(int $secretaryId)
    {
        $secretary = $this->secretaryOnOrganizationRepository->get($secretaryId);
        if ($secretary == null){
            return;
        }

        $secretaries = $this->secretaryOnOrganizationRepository->getByUserId($secretary->getUserId());

        if (count($secretaries) == 1){
            $user = $secretary->getUser();
            $user->setRoleId($this->roleRepository->getByName(AuthorizeMiddleware::SIMPLE_USER)->getId());
            $this->userRepository->update($user);
        }

        $this->secretaryOnOrganizationRepository->delete($secretaryId);
    }

    public function getSecretariesOnOrganization(int $organizationId)
    {
        return $this->secretaryOnOrganizationRepository->getByOrgId($organizationId);
    }
}