<?php

namespace App\Application\Actions\Result;
use App\Application\Actions\Action;
use App\Services\AccessService\AccessService;
use App\Services\Result\ResultService;
use http\Message\Body;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ResultAction extends Action
{
    private $resultService;
    private $accessService;
    public function __construct(ResultService $resultService, AccessService $accessService)
    {
        $this->resultService = $resultService;
        $this->accessService = $accessService;
    }


    /**
     *
     * @SWG\Get(
     *   path="/api/v1/event/{eventId}/user/{userId}/result",
     *   summary="получает результаты для определенного участника",
     *   tags={"Result"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="eventId", type="integer", description="id мероприятия"),
     *     @SWG\Parameter(in="query", name="userId", type="integer", description="id пользователя"),
     *   @SWG\Response(response=200, description="OK", @SWG\Schema(ref="#/definitions/resultForUser")),
     *  @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     */
    public function getResultsOfUserInEvent(Request $request, Response $response, $args): Response
    {
        $eventId = (int)$args['eventId'];
        $userId = (int)$args['userId'];
        $result = $this->resultService->getResultsUfUserInEvent($eventId, $userId);

        if ($result == []){
            return $response->withStatus(404);
        }

        return $this->respond(200, ['groups' => $result['groups'],
            'ageCategory' => $result['ageCategory'],
            'badge' => $result['badge'],
            'countTestsForBronze' => $result['countTestsForBronze'],
            'countTestForSilver' => $result['countTestForSilver'],
            'countTestsForGold' => $result['countTestsForGold'],
            'orgId' => $result['orgId'],
            'eventId' => $result['eventId'],
            'id' => $result['id'],
            'name' => $result['name'],
            'teamName' => $result['teamName'],
            'teamId' => $result['teamId'],
            'dateOfBirth' => $result['dateOfBirth']
        ],  $response);
    }

    /**
     *
     * @SWG\Get(
     *   path="/api/v1/event/{eventId}/allResults",
     *   summary="получает все результаты",
     *   tags={"Result"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="eventId", type="integer", description="id мероприятия"),
     *   @SWG\Response(response=200, description="OK", @SWG\Schema(ref="#/definitions/allResults")),
     *   @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     */
    public function getAllResults(Request $request, Response $response, $args)
    {
        $eventId = (int)$args['eventId'];
        $result = $this->resultService->getAllResults($eventId);
        return $this->respond(200, ['trials' => $result['trials'], 'participants' => $result['participants']], $response);
    }

    /**
     *
     * @SWG\Get(
     *   path="/api/v1/event/{eventId}/allResults/csv",
     *   summary="получает документ в формате csv",
     *   tags={"Result"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="eventId", type="integer", description="id мероприятия"),
     *   @SWG\Response(response=200, description="OK"),
     *   @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     */
    public function getAllResultsInXlsx(Request $request, Response $response, $args)
    {
        $eventId = (int)$args['eventId'];
        $doc = $this->resultService->getAllResultsInXlsxFormat($eventId);
        $response->getBody()->write($doc);
        return $response;
    }

    /**
     *
     * @SWG\Get(
     *   path="/api/v1/trialInEvent/{trialInEventId}/result",
     *   summary="получает результаты для пользователей определенного вида спорта в мероприятии",
     *   tags={"Result"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="trialInEventId", type="integer", description="id испытания в мероприятии"),
     *   @SWG\Response(response=200, description="OK", @SWG\Property(type="array", @SWG\Items(ref="#/definitions/participantsInTrial")),
     *  @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     */
    public function getResultsOnTrialInEvent(Request $request, Response $response, $args): Response
    {
        $trialInEventId = (int)$args['trialInEventId'];
        $result = $this->resultService->getResultsForTrial($trialInEventId);
        return $this->respond(200, $result, $response);
    }

    /**
     *
     * @SWG\Put(
     *   path="/api/v1/resultTrialInEvent/{resultTrialInEventId}",
     *   summary="меняет результат(локальный админ, секретарь)",
     *   tags={"Result"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="resultTrialInEventId", type="integer", description="id опрделенного результата"),
     *   @SWG\Parameter(in="body", name="body", @SWG\Schema(@SWG\Property(property="firstResult", type="string"))),
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
    public function updateResult(Request $request, Response $response, $args): Response
    {
        if ($this->tokenWithError($response, $request)) {
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
        $userEmail = $request->getHeader('userEmail')[0];
        $resultTrialInEventId = (int)$args['resultTrialInEventId'];
        $rowParams = json_decode($request->getBody()->getContents(), true);
        $access = $this->accessService->hasAccessUpdateResult($userRole, $userEmail, $resultTrialInEventId);

        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            /**@var $access array*/
            return $this->respond(400, ['errors' => $access], $response);
        }

        $this->resultService->updateResult($resultTrialInEventId, $rowParams['firstResult']);
        return $response->withStatus(200);
    }
}