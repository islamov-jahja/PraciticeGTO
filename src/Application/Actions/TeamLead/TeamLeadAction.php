<?php

namespace App\Application\Actions\TeamLead;

use App\Application\Actions\Action;
use App\Application\Actions\ActionError;
use App\Application\Middleware\AuthorizeMiddleware;
use App\Domain\Models\TeamLead\TeamLead;
use App\Services\AccessService\AccessService;
use App\Services\TeamLead\TeamLeadService;
use App\Validators\Auth\EmailValidator;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class TeamLeadAction extends Action
{
    private $teamLeadService;
    private $accessService;

    public function __construct(TeamLeadService $teamLeadService, AccessService $accessService)
    {
        $this->teamLeadService = $teamLeadService;
        $this->accessService = $accessService;
    }


    /**
     *
     * @SWG\Post(
     *   path="/api/v1/team/{teamId}/teamLead",
     *   summary="добавляет тренера к команде(секретарь, локальный админ)",
     *   tags={"TeamLead"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="teamId", type="integer", description="id команды"),
     *   @SWG\Parameter(in="body", name="body", @SWG\Schema(@SWG\Property(property="email", type="string"))),
     *   @SWG\Response(response=200, description="OK", @SWG\Schema(@SWG\Property(property="id", type="integer"),)),
     *  @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     *
     */
    public function add(Request $request, Response $response, $args):Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];

        if ($userRole == AuthorizeMiddleware::TEAM_LEAD){
            return $response->withStatus(403);
        }

        $userEmail = $request->getHeader('userEmail')[0];
        $teamId = (int)$args['teamId'];
        $access = $this->accessService->hasAccessWorkWithTeamWithId($userRole, $userEmail, $teamId);
        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            /**@var $access array*/
            return $this->respond(400, ['errors' => $access], $response);
        }

        $rowParams = json_decode($request->getBody()->getContents(), true);

        $errors = (new EmailValidator())->validate($rowParams);
        if (count($errors) > 0) {
            return $this->respond(400, ['errors' => $errors], $response);
        }

        $id = $this->teamLeadService->add($rowParams['email'], $teamId);

        if ($id == -1){
            $error = new ActionError(ActionError::BAD_REQUEST, 'Данный пользователь уже имеет другую роль');
            return $this->respond(400, ['errors' => array($error->jsonSerialize())], $response);
        }

        if ($id == -2){
            $error = new ActionError(ActionError::BAD_REQUEST, 'Данный тренер уже присутствует в этой команде');
            return $this->respond(400, ['errors' => array($error->jsonSerialize())], $response);
        }

        if ($id == -3){
            $error = new ActionError(ActionError::BAD_REQUEST, 'Данного пользователя не существует');
            return $this->respond(400, ['errors' => array($error->jsonSerialize())], $response);
        }
        return $this->respond(200, ['id' => $id], $response);
    }

    /**
     *
     * @SWG\Delete(
     *   path="/api/v1/teamLead/{teamLeadId}",
     *   summary="удаляет тренера от команды(секретарь, локальный админ)",
     *   tags={"TeamLead"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="teamLeadId", type="integer", description="id тренера"),
     *   @SWG\Response(response=200, description="OK"),
     *  @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     *
     */
    public function delete(Request $request, Response $response, $args):Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
        $userEmail = $request->getHeader('userEmail')[0];

        if ($userRole == AuthorizeMiddleware::TEAM_LEAD){
            return $response->withStatus(403);
        }

        $teamLeadId = (int)$args['teamLeadId'];
        $teamLead = $this->teamLeadService->get($teamLeadId);
        if ($teamLead == null){
            return $response;
        }

        $access = $this->accessService->hasAccessWorkWithTeamWithId($userRole, $userEmail, $teamLead->getTeamId());
        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            /**@var $access array*/
            return $this->respond(400, ['errors' => $access], $response);
        }

        $this->teamLeadService->delete($teamLeadId);
        return $response;
    }

    /**
     *
     * @SWG\Get(
     *   path="/api/v1/team/{teamId}/teamLead",
     *   summary="получает список всех тренеров, относящихся к команде(все)",
     *   tags={"TeamLead"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="teamId", type="integer", description="id команды"),
     *   @SWG\Response(response=200, description="OK", @SWG\Property(type="array", @SWG\Items(ref="#/definitions/teamLead")),
     *  @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     *
     */

    public function getAllForTeam(Request $request, Response $response, $args):Response
    {
        $teamId = (int)$args['teamId'];
        $teamLeads = $this->teamLeadService->getAllForTeam($teamId);
        $teamLeadsInArray = [];
        foreach ($teamLeads as $teamLead){
            $teamLeadsInArray[] = $teamLead->toArray();
        }

        return $this->respond(200, $teamLeadsInArray, $response);
    }
}