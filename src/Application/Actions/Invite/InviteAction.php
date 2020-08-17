<?php


/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 28.11.2019
 * Time: 10:21
 */

namespace App\Application\Actions\Invite;


use App\Application\Actions\Action;
use App\Application\Actions\ActionError;
use App\Domain\Models\User\UserCreater;
use App\Services\Invite\Invite;
use App\Validators\Invite\InviteValidator;
use App\Validators\ValidateStrategy;
use DateTime;
use Monolog\Logger;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Symfony\Component\Validator\Constraints as Assert;

class InviteAction extends Action
{
    private $inviteService;
    private $validator;

    public function __construct(Invite $invite)
    {
        $this->inviteService = $invite;
    }

    /**
     *
     * * @SWG\Post(
     *   path="/api/v1/auth/invite",
     *   summary="Отправка приглашения на регистрацию",
     *   operationId="Отправка приглашения на регистрацию",
     *   tags={"User"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string"),
     *   @SWG\Parameter(in="body", name="body", @SWG\Schema(
     *      @SWG\Property(property="email", type="string"),
     *      @SWG\Property(property="name", type="string"),
     *      @SWG\Property(property="gender", type="integer"),
     *      @SWG\Property(property="dateOfBirth", type="string"),
     *    )),
     *   @SWG\Response(response=200, description="OK"),
     *  @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     ))
     * )
     *
     */

    public function sendInviteToRegistration(Request $request, Response $response):Response
    {
        $params = json_decode($request->getBody()->getContents(), true);

        $validator = new InviteValidator();
        $errors = $validator->validate($params);

        if (count($errors) > 0){
            return $this->respond(400, ['errors' => $errors], $response);
        }

        $response = $response->withHeader('Access-Control-Allow-Headers', 'Authorization');
        $user = UserCreater::createModel([
            'id' => -1,
            'name' => $params['name'],
            'password' => '',
            'email' => $params['email'],
            'roleId' => -1,
            'dateTime' => new DateTime(),
            'isActivity' => 0,
            'dateOfBirth' => new DateTime($params['dateOfBirth']),
            'gender' => $params['gender']
        ]);

        return $this->inviteService->sendInviteToRegistration($user, $response);
    }
}