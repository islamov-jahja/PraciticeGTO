<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 25.11.2019
 * Time: 1:24
 */

namespace App\Application\Actions\User;

use App\Application\Actions\ActionError;
use App\Application\Middleware\AuthorizeMiddleware;
use App\Validators\Auth\LoginValidator;
use App\Validators\Auth\RegistrationValidator;
use App\Validators\ValidateStrategy;
use Symfony\Component\Validator\Constraints as Assert;
use App\Services\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Actions\Action;
use \App\Services\Auth\Auth as AuthService;

class AuthAction extends Action
{
    private $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

 /**
*
*  @SWG\Post(
*   path="/api/v1/auth/confirmAccount",
*   summary="делает аккаунт пользователя действительным с переданным паролем",
*   operationId="",
*   tags={"User"},
*   @SWG\Parameter(in="header", name="Authorization", type="string"),
*   @SWG\Parameter(in="body", name="body", @SWG\Schema(
*      @SWG\Property(property="password", type="string", description="length min 6 symbols")
*    )),
*   @SWG\Response(response=200, description="OK"),
*   @SWG\Response(response=400, description="Error", @SWG\Schema(
*          @SWG\Property(property="errors", type="array", @SWG\Items(
*              @SWG\Property(property="type", type="string"),
*              @SWG\Property(property="description", type="string")
*          ))
*     ))
* )
*
*/
    public function confirmAccount(Request $request, Response $response, $args): Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }

        $params = json_decode($request->getBody()->getContents(), true);
        $params['token'] = $request->getHeader('Authorization')[0];
        $validator = new RegistrationValidator();
        $errors = $validator->validate($params);

        if (count($errors) > 0){
            return $this->respond(400, ['errors' => $errors], $response);
        }

        return $this->auth->confirmAccount($params, $response);
    }

    /**
     *
     * * @SWG\Post(
     *   path="/api/v1/auth/login",
     *   summary="авторизует пользователей, возвращая аксесс и рефреш токены",
     *   operationId="авторизует пользователей, возвращая аксесс и рефреш токены",
     *   tags={"User"},
     *   @SWG\Parameter(in="body", name="body", @SWG\Schema(
     *      @SWG\Property(property="email", type="string"),
     *      @SWG\Property(property="password", type="string", description="length min 6 symbols")
     *    )),
     *   @SWG\Response(response=200, description="OK", @SWG\Schema(
     *              @SWG\Property(property="accessToken", type="string"),
     *              @SWG\Property(property="refreshToken", type="string"),
     *              @SWG\Property(property="userId", type="integer"),
     *              @SWG\Property(property="role", type="string"),
     *              @SWG\Property(property="organizationId", type="integer")
     *          )),
     *   @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     ))
     * )
     *
     */


    public function login(Request $request, Response $response, $args): Response
    {
        $params = json_decode($request->getBody()->getContents(), true);

        $validator = new LoginValidator();
        $errors = $validator->validate($params);

        if (count($errors) > 0){
            return $this->respond(400, ['errors' => $errors], $response);
        }

        return $this->auth->login($params, $response);
    }

    /**
     *
     * @SWG\Get(
     *   path="/api/v1/auth/info",
     *   summary="возвращает информацию об аккаунте",
     *   tags={"User"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="teamId", type="integer", description="id команды"),
     *   @SWG\Response(response=200, description="OK", @SWG\Schema(
     *     @SWG\Property(property="name", type="string"),
     *     @SWG\Property(property="gender", type="integer"),
     *     @SWG\Property(property="dateOfBirth", type="string"),
     *     @SWG\Property(property="role", type="string"),
     *     ))
     * )
     *
     */
    public function getInfo(Request $request, Response $response, $args): Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }

        $data = [
            'email' => $request->getHeader('userEmail')[0],
            'name' => $request->getHeader('name')[0],
            'gender' => $request->getHeader('gender')[0],
            'dateOfBirth' => $request->getHeader('dateOfBirth')[0]
        ];

        return $this->respond(200, $data, $response);
    }

    /**
     *
     * * @SWG\Post(
     *   path="/api/v1/auth/refresh",
     *   summary="возвращает новую пару аксесс и рефреш токенов",
     *   operationId="возвращает новую пару аксесс и рефреш токенов",
     *   tags={"User"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string"),
     *   @SWG\Response(response=200, description="OK", @SWG\Schema(
     *              @SWG\Property(property="accessToken", type="string"),
     *              @SWG\Property(property="refreshToken", type="string")
     *          )),
     *   @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     ))
     * )
     *
     */

    public function refresh(Request $request, Response $response, $args): Response
    {
        if (isset($request->getHeader('error')[0])){
            return $this->respond(400, ['errors' => array(new ActionError(ActionError::UNAUTHENTICATED, $request->getHeader('error')[0]))], $response);
        }

        $params['refreshToken'] = $request->getHeader('Authorization')[0] ?? null;
        return $this->auth->refresh($params, $response);
    }
}