<?php

namespace App\Application\Actions\Team;
use App\Application\Actions\Action;
use App\Application\Actions\ActionError;
use App\Application\Middleware\AuthorizeMiddleware;
use App\Services\AccessService\AccessService;
use App\Services\Team\TeamService;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class TeamAction extends Action
{
    private $temaService;
    private $accessService;
    public function __construct(TeamService $teamService, AccessService $accessService)
    {
        $this->temaService = $teamService;
        $this->accessService = $accessService;
    }

    /**
     *
     * @SWG\Post(
     *   path="/api/v1/organization/{id}/event/{eventId}/team",
     *   summary="добавляет команду в определенное мероприятие(локальный админ или секретарь этого мероприятия)",
     *   tags={"Team"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Parameter(in="query", name="eventId", type="integer", description="id мероприятия"),
     *   @SWG\Parameter(in="body", name="body", @SWG\Schema(@SWG\Property(property="name", type="string"))),
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

    public function add(Request $request, Response $response, $args): Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
        $userEmail = $request->getHeader('userEmail')[0];

        $access = $this->accessService->hasAccessWorkWithTeam($userRole, (int)$args['id'], (int)$args['eventId'], $userEmail);
        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            /**@var $access array*/
            return $this->respond(400, ['errors' => $access], $response);
        }

        $rowParams = json_decode($request->getBody()->getContents(), true);
        $teamId = $this->temaService->add($rowParams['name'], (int)$args['eventId']);
        return  $this->respond(200, ['id' => $teamId], $response);
    }

    /**
     *
     * @SWG\Post(
     *   path="/api/v1/team/{teamId}/confirm",
     *   summary="делает подтвержденным всех членов команды(секретарь мероприятия, локальный админ)",
     *   tags={"Team"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="teamId", type="integer", description="id организации"),
     *   @SWG\Response(response=200, description="OK"),
     *   @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     *
     */
    public function confirm(Request $request, Response $response, $args): Response
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

        $this->temaService->confirm($teamId);
        return $response;
    }

    /**
     *
     * @SWG\Get(
     *   path="/api/v1/team",
     *   summary="получает те команды, которые относятся к определенному пользователю, который передан",
     *   tags={"Team"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Response(response=200, description="OK", @SWG\Schema(ref="#/definitions/teamResponse")),
     *   @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     *
     */
    public function getListForUser(Request $request, Response $response, $args): Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
        $userEmail = $request->getHeader('userEmail')[0];

        $teams = $this->temaService->getListForUser($userEmail, $userRole);
        return $this->respond(200, $teams, $response);
    }

    /**
     *
     * @SWG\Get(
     *   path="/api/v1/team/{teamId}",
     *   summary="получает данные определенной команды, относящейся к определенному мероприятию",
     *   tags={"Team"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Parameter(in="query", name="teamId", type="integer", description="id мероприятия"),
     *   @SWG\Response(response=200, description="OK", @SWG\Schema(ref="#/definitions/teamResponse")),
     *   @SWG\Response(response=404, description="Not found")
     * )
     *
     */
    public function get(Request $request, Response $response, $args): Response
    {
        $teamId = (int)$args['teamId'];
        $team = $this->temaService->get($teamId);
        if ($team == null){
            return $response->withStatus(404);
        }
        return $this->respond(200, $team, $response);
    }

    /**
     *
     * @SWG\Get(
     *   path="/api/v1/organization/{id}/event/{eventId}/team",
     *   summary="получает данные команд, относящихся к определенному мероприятию",
     *   tags={"Team"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Parameter(in="query", name="eventId", type="integer", description="id мероприятия"),
     *   @SWG\Response(response=200, description="OK", @SWG\Property(type="array", @SWG\Items(ref="#/definitions/teamResponse")),
     *  @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     *
     */
    public function getAll(Request $request, Response $response, $args): Response
    {
        $teams = $this->temaService->getAll((int)$args['id'], (int)$args['eventId']);
        $teamsInArray = [];

        foreach ($teams as $team){
            $teamsInArray[] = $team->toArray();
        }

        return $this->respond(200, $teamsInArray, $response);
    }

    /**
     *
     * @SWG\Delete(
     *   path="/api/v1/team/{teamId}",
     *   summary="удаляет определенную команду, относящейся к определенному мероприятию(локальный админ или секретарь, которые имеет право редактировать данное мероприятие)",
     *   tags={"Team"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Parameter(in="query", name="teamId", type="integer", description="id мероприятия"),
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
    public function delete(Request $request, Response $response, $args): Response
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


        $this->temaService->delete($teamId);
        return $response;
    }

    /**
     *
     * @SWG\Put(
     *   path="/api/v1/team/{teamId}",
     *   summary="редактирует данные команды(локальный админ или секретарь, который имеет право редактирования данного мероприятия)",
     *   tags={"Team"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Parameter(in="query", name="eventId", type="integer", description="id мероприятия"),
     *   @SWG\Parameter(in="body", name="body", @SWG\Schema(@SWG\Property(property="name", type="string"))),
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
    public function update(Request $request, Response $response, $args): Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
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
        $name = $rowParams['name'] ?? '';
        if ($name == ''){
            $error = new ActionError(ActionError::BAD_REQUEST, 'Название не может быть пустым');
            return $this->respond(400, ['errors' => array($error->jsonSerialize())], $response);
        }

        $this->temaService->update($name, $teamId);
        return $response;
    }
}