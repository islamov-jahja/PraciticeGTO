<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 28.11.2019
 * Time: 10:24
 */

namespace App\Services\Invite;
use App\Application\Actions\ActionError;
use App\Application\Middleware\AuthorizeMiddleware;
use App\Domain\Models\IModel;
use App\Domain\Models\User\User;
use App\Persistance\Repositories\Role\RoleRepository;
use App\Persistance\Repositories\User\RegistrationTokenRepository;
use App\Persistance\Repositories\User\UserRepository;
use App\Services\EmailSendler\EmailSendler;
use App\Services\Token\Token;
use DateTime;
use DateTimeZone;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;

class Invite
{
    private $regTokenRep;
    private $emailSendler;
    private $userRepository;
    private $roleRepository;

    public function __construct(RegistrationTokenRepository $registrationTokenRepository, EmailSendler $emailSendler, UserRepository $userRepository, RoleRepository $roleRepository)
    {
        $this->emailSendler = $emailSendler;
        $this->regTokenRep = $registrationTokenRepository;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    /**@var $user User*/
    public function sendInviteToRegistration(IModel $user, Response $response):Response
    {
        if ($this->userRepository->getByEmail($user->getEmail()) != null){
            $error = new ActionError(ActionError::BAD_REQUEST, 'Такой пользователь существует');
            $response->getBody()->write(json_encode(['errors' => array($error->jsonSerialize())]));
            return $response->withStatus(400);
        }

        $token = Token::getEncodedToken([
            'email' => $user->getEmail(),
            'type' => 'access token',
            'liveTime' => 24 * 7 * 3600,
            'role' => '',
            'addedTime' => (new DateTime)
                ->setTimezone(new DateTimeZone('europe/moscow'))
                ->format('Y-m-d H:i:s')
        ]);

        try {
            $this->emailSendler->sendInvite($user->getEmail(), $token);
        }catch (Exception $err){
            $error = new ActionError(ActionError::BAD_REQUEST, 'Такой почты не существует');
            $response->getBody()->write(json_encode(['errors' => array()]));
            return $response->withStatus(400);
        }

        $role = $this->roleRepository->getByName(AuthorizeMiddleware::SIMPLE_USER);
        $user->setRoleId($role->getId());
        $this->userRepository->add($user);

        $this->regTokenRep->addTokenToDB($token);
        return $response;
    }

    /*public function valid(array $params, Response $response):Response
    {
        $tokenDataFromDb = $this->regTokenRep->getTokenFromDB($params['token']);

        if (!isset($tokenDataFromDb[0]->token)){
            return $response->withStatus(404);
        }

        $response->getBody()->write(json_encode(['email' => $params['email']]));

        return $response->withStatus(200);
    }*/
}