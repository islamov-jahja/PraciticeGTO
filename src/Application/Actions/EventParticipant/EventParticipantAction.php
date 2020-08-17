<?php

namespace App\Application\Actions\EventParticipant;

use App\Application\Actions\Action;
use App\Application\Actions\ActionError;
use App\Application\Middleware\AuthorizeMiddleware;
use App\Services\AccessService\AccessService;
use App\Services\EventParticipant\EventParticipantService;
use App\Validators\Auth\EmailValidator;
use App\Validators\User\UserValidator;
use DateTime;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class EventParticipantAction extends Action
{
    private $accessService;
    private $eventParticipantService;

    public function __construct(AccessService $accessService, EventParticipantService $eventParticipantService)
    {
        $this->accessService = $accessService;
        $this->eventParticipantService = $eventParticipantService;
    }

    /**
     *
     * * @SWG\Get(
     *   path="/api/v1/team/{teamid}/participant",
     *   summary="получение участников команды",
     *   tags={"ParticipantEvent"},
     *   @SWG\Parameter(in="query", name="eventId", type="integer", description="id мероприятия"),
     *   @SWG\Response(response=200, description="OK",
     *          @SWG\Property(type="array", @SWG\Items(ref="#/definitions/participantEvent"))
     *   ),
     * )
     *
     */
    public function getAllForTeam(Request $request, Response $response, $args):Response
    {
        $participantsArray = [];
        $teamId = (int)$args['teamId'];
        $participants = $this->eventParticipantService->getAllForTeam($teamId);

        foreach ($participants as $participant){
            $participantsArray[] = $participant->toArray();
        }

        return $this->respond(200, $participantsArray, $response);
    }

    /**
     *
     * @SWG\Post(
     *   path="/api/v1/team/{teamId}/participant",
     *   summary="добавление участника в команду(тренер той команды, которая передана или же локальный админ и секретарь данного мероприятия)",
     *   tags={"ParticipantEvent"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="teamId", type="integer", description="id команды, к которой добавляем участника"),
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
        if ($this->tokenWithError($response, $request)) {
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
        $userEmail = $request->getHeader('userEmail')[0];
        $params = json_decode($request->getBody()->getContents(), true);

        $errors = (new EmailValidator())->validate($params);
        if (count($errors) > 0) {
            return $this->respond(400, ['errors' => $errors], $response);
        }

        $teamId = (int)$args['teamId'];
        $access = $this->accessService->hasAccessAddParticipantToTeam($userEmail, $userRole, $teamId, $params['email']);

        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            return $this->respond(400, ['errors' => $access], $response);
        }

        $id = $this->eventParticipantService->addToTeam($params['email'], false, $teamId);
        return $this->respond(200, ['id' => $id], $response);
    }

    /**
     *
     * @SWG\Post(
     *   path="/api/v1/event/{eventId}/participant",
     *   summary="добавление участника в мероприятие без команды(локальный админ и секретарь данного мероприятия)",
     *   tags={"ParticipantEvent"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="eventId", type="integer", description="id команды, к которой добавляем участника"),
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
    public function addParticipantWithoutTeam(Request $request, Response $response, $args):Response
    {
        if ($this->tokenWithError($response, $request)) {
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
        $userEmail = $request->getHeader('userEmail')[0];
        $params = json_decode($request->getBody()->getContents(), true);
        $emailOfUserToAdd = $params['email'];

        if (!filter_var($emailOfUserToAdd, FILTER_VALIDATE_EMAIL)){
            $error = new ActionError(ActionError::BAD_REQUEST, 'Email не соответвует формату почты');
            $response->getBody()->write(json_encode(['errors' => array($error->jsonSerialize())]));
            return $response->withStatus(400);
        }

        $eventId = (int)$args['eventId'];
        $access = $this->accessService->hasAccessAddParticipantToEvent($userEmail, $userRole, $eventId, $emailOfUserToAdd);

        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            return $this->respond(400, ['errors' => $access], $response);
        }

        $id = $this->eventParticipantService->addToEvent($emailOfUserToAdd, false, $eventId);
        return $this->respond(200, ['id' => $id], $response);
    }

    /**
     *
     * * @SWG\Get(
     *   path="/api/v1/event/{eventId}/participant",
     *   summary="получение участников мероприятия",
     *   tags={"ParticipantEvent"},
     *   @SWG\Parameter(in="query", name="eventId", type="integer", description="id мероприятия"),
     *   @SWG\Response(response=200, description="OK",
     *          @SWG\Property(type="array", @SWG\Items(ref="#/definitions/participantEvent"))
     *   ),
     * )
     *
     */
    public function getAllForEvent(Request $request, Response $response, $args):Response
    {
        $eventId = (int) $args['eventId'];
        $participants = $this->eventParticipantService->getAllForEvent($eventId);

        $participantsInArray = [];

        foreach ($participants as $participant){
            $participantsInArray[] = $participant->toArray();
        }

        return $this->respond(200, $participantsInArray, $response);
    }

    /**
     *
     * @SWG\Post(
     *   path="/api/v1/event/{eventId}/participant/{participantId}",
     *   summary="локальный админ или секретарь могут приянять заявку от participantId которй подал его на участие в мероприятии(локальный админ и секретарь этого мероприятия)",
     *   tags={"ParticipantEvent"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="eventId", type="integer", description="id мероприятия"),
     *   @SWG\Parameter(in="query", name="participantId", type="integer", description="id владельца заявки"),
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
    public function confirmApply(Request $request, Response $response, $args):Response
    {
        if ($this->tokenWithError($response, $request)) {
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
        $userEmail = $request->getHeader('userEmail')[0];

        $participantId = (int)$args['participantId'];

        if ($userRole == AuthorizeMiddleware::TEAM_LEAD){
            return $response->withStatus(403);
        }

        $access = $this->accessService->hasAccessWorkWithParticipant($userEmail, $participantId, $userRole);

        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            /**@var $access array*/
            return $this->respond(400, ['errors' => $access], $response);
        }

        $this->eventParticipantService->confirmApply($participantId);
        return $response;
    }

    /**
     *
     * @SWG\Delete(
     *   path="/api/v1/event/{eventId}/participant/{participantId}",
     *   summary="Удаляет участника из мероприятия(локальный админ и секретарь этого мероприятия, тренер команды)",
     *   tags={"ParticipantEvent"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="eventId", type="integer", description="id мероприятия"),
     *   @SWG\Parameter(in="query", name="participantId", type="integer", description="id владельца заявки"),
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
    public function deleteParticipant(Request $request, Response $response, $args):Response
    {
        if ($this->tokenWithError($response, $request)) {
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
        $userEmail = $request->getHeader('userEmail')[0];

        $participantId = (int)$args['participantId'];

        $access = $this->accessService->hasAccessWorkWithParticipant($userEmail, $participantId, $userRole);

        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            /**@var $access array*/
            return $this->respond(400, ['errors' => $access], $response);
        }

        $this->eventParticipantService->delete($participantId);
        return $response;
    }

    /**
     *
     * @SWG\Put(
     *   path="/api/v1/participant/{participantId}",
     *   summary="меняет данные участника(тренер команды, в котором находится участник)",
     *   tags={"ParticipantEvent"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="participantId", type="integer", description="id участника"),
     *   @SWG\Parameter(in="body", name="body", @SWG\Schema(@SWG\Property(property="name", type="string"), @SWG\Property(property="dateOfBirth", type="string"), @SWG\Property(property="gender", type="integer"), @SWG\Property(property="uid", type="string"))),
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
    public function updateUserOnTeam(Request $request, Response $response, $args):Response
    {
        if ($this->tokenWithError($response, $request)) {
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
        $userEmail = $request->getHeader('userEmail')[0];

        if (in_array($userRole, [AuthorizeMiddleware::SECRETARY, AuthorizeMiddleware::SECRETARY])){
            return $response->withStatus(403);
        }

        $participantId = (int)$args['participantId'];

        $access = $this->accessService->hasAccessWorkWithParticipant($userEmail, $participantId, $userRole);

        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            /**@var $access array*/
            return $this->respond(400, ['errors' => $access], $response);
        }

        $rowParams = json_decode($request->getBody()->getContents(), true);

        $errors = (new UserValidator())->validate($rowParams);
        if (count($errors) > 0) {
            return $this->respond(400, ['errors' => $errors], $response);
        }

        $this->eventParticipantService->updateUserOnTeam($rowParams['name'], $rowParams['gender'], (new DateTime($rowParams['dateOfBirth'])), $participantId, $rowParams['uid'] ?? '');
        return $response;
    }
}