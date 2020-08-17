<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 27.11.2019
 * Time: 2:49
 */

namespace App\Services\Auth;


use App\Application\Actions\ActionError;
use App\Application\Middleware\AuthorizeMiddleware;
use App\Persistance\Repositories\LocalAdmin\LocalAdminRepository;
use App\Persistance\Repositories\User\RefreshTokenRepository;
use App\Persistance\Repositories\User\RegistrationTokenRepository;
use App\Persistance\Repositories\User\UserRepository;
use App\Services\Token\Token;
use DateTime;
use DateTimeZone;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;

class Auth
{
    private $userRepository;
    private $localAdminRepository;
    private $refTokenRep;
    private $regTokenRep;

    public function __construct(UserRepository $userRepository, RefreshTokenRepository $rToken, RegistrationTokenRepository $regToken, LocalAdminRepository $localAdminRepository)
    {
        $this->userRepository = $userRepository;
        $this->refTokenRep = $rToken;
        $this->regTokenRep = $regToken;
        $this->localAdminRepository  = $localAdminRepository;
    }

    public function login(array $params, Response $response):Response
    {
        if (!$this->userRepository->userIsSetOnDB($params['email'], Token::getEncodedPassword($params['password']))){
            $error = new ActionError(ActionError::VERIFICATION_ERROR, 'Неверный логин или пароль');
            $response->getBody()->write(json_encode(['errors' => array($error->jsonSerialize())]));
            return $response->withStatus(400);
        }
        $role = $this->userRepository->getRoleOfUser($params['email']);
        $user = $this->userRepository->getByEmail($params['email']);
        $refreshToken = Token::getEncodedToken([
            'email' => $params['email'],
            'role' => $role,
            'type' => 'refresh token',
            'liveTime' => 24 * 7 * 3600,
            'addedTime' => (new DateTime)
                ->setTimezone(new DateTimeZone('europe/moscow'))
                ->format('Y-m-d H:i:s')
        ]);


        $accessToken = $this->getAccessToken($params['email']);

        $rToken = new RefreshTokenRepository();
        $rToken->deleteRefreshTokenWithEmail($params['email']);
        $rToken->addRefreshToken($refreshToken, $params['email']);

        $responseData = [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'userId' => $user->getId(),
            'role' => $role
        ];

        $organizationId = -1;

        if (in_array($role, [AuthorizeMiddleware::LOCAL_ADMIN, AuthorizeMiddleware::SECRETARY])){
            switch ($role){
                case AuthorizeMiddleware::LOCAL_ADMIN:{
                    $organizationId = $this->localAdminRepository->getOrganizationIdFilteredByEmail($params['email']);
                    $responseData['organizationId'] = $organizationId;
                }
            }
        }

        $response->getBody()->write(json_encode($responseData));

        return $response;
    }

    public function refresh(array $params, Response $response):Response
    {

        if(!$this->refTokenRep->refreshTokenIsSet($params['refreshToken'])){
            $errors[] = new ActionError(ActionError::VALIDATION_ERROR, 'Такого токена не существует');
            $response->getBody()->write(json_encode(['errors' => $errors]));
            return $response;
        }

        $decodedToken = (array)Token::getDecodedToken($params['refreshToken']);

        $refreshToken = Token::getEncodedToken([
            'email' => $decodedToken['email'],
            'role' => $decodedToken['role'],
            'type' => 'refresh token',
            'liveTime' => 24 * 7 * 3600,
            'addedTime' => (new DateTime)
                ->setTimezone(new DateTimeZone('europe/moscow'))
                ->format('Y-m-d H:i:s')
        ]);

        $accessToken = $this->getAccessToken($decodedToken['email']);
        $this->refTokenRep->updateRefreshTokenWithEmail($decodedToken['email'], $refreshToken);

        $response->getBody()->write(json_encode([
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken
        ]));

        return $response;
    }

    private function getAccessToken($email)
    {
        $role = $this->userRepository->getRoleOfUser($email);
        $user = $this->userRepository->getByEmail($email);

        return Token::getEncodedToken([
            'gender' => $user->getGender(),
            'dateOfBirth' => $user->getDateOfBirth(),
            'name' => $user->getName(),
            'userId' => $user->getId(),
            'email' => $email,
            'role' => $role,
            'type' => 'acess token',
            'liveTime' => 1800,
            'addedTime' => (new DateTime())
                ->setTimezone(new DateTimeZone('europe/moscow'))
                ->format('Y-m-d H:i:s')
        ]);
    }

    public function confirmAccount(array $params, Response $response):Response
    {
        $tokenDataFromDb = $this->regTokenRep->getByTokenValue($params['token']);
        if ($tokenDataFromDb->getToken() == null){
            $error = new ActionError(ActionError::BAD_REQUEST, 'Невалидный токен');
            $response->getBody()->write(json_encode(['errors' => array($error->jsonSerialize())]));
            return $response->withStatus(400);
        }

        $this->regTokenRep->deleteTokenFromDB($params['token']);
        $jwtData = (array)Token::getDecodedToken($params['token']);
        $password = Token::getEncodedPassword($params['password']);

        $user = $this->userRepository->getByEmail($jwtData['email']);
        $user->setPassword($password);
        $user->setIsActivity();

        $this->userRepository->update($user);

        return $response->withStatus(200);
    }
}