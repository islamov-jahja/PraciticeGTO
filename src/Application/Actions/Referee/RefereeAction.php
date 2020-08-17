<?php

namespace App\Application\Actions\Referee;
use App\Application\Actions\Action;
use App\Services\AccessService\AccessService;
use App\Services\Referee\RefereeService;
use App\Validators\Auth\EmailValidator;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class RefereeAction extends Action
{
    private $accessService;
    private $refereeService;
    public function __construct(AccessService $accessService, RefereeService $refereeService)
    {
        $this->accessService = $accessService;
        $this->refereeService = $refereeService;
    }

    /**
     *
     * @SWG\Post(
     *   path="/api/v1/organization/{id}/referee",
     *   summary="добавляет судью в справочник организации(локальный админ)",
     *   tags={"Referee"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Parameter(in="body", name="body", @SWG\Schema(@SWG\Property(property="email", type="string"))),
     *   @SWG\Response(response=200, description="OK", @SWG\Schema(@SWG\Property(property="id", type="integer"),)),
     *   @SWG\Response(response=403, description=""),
     *  @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     *
     */
    public function create(Request $request, Response $response, $args):Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
        $localAdminEmail = $request->getHeader('userEmail')[0];
        $organizationId = (int)$args['id'];
        $rowParams = json_decode($request->getBody()->getContents(), true);
        $errors = (new EmailValidator())->validate($rowParams);
        if (count($errors) > 0) {
            return $this->respond(400, ['errors' => $errors], $response);
        }

        $refereeEmail = $rowParams['email'];

        $access = $this->accessService->hasAccessAddRefereeToOrganization($userRole, $localAdminEmail, $organizationId, $refereeEmail);
        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            /**@var $access array*/
            return $this->respond(400, ['errors' => $access], $response);
        }

        $id = $this->refereeService->addToOrganization($organizationId, $refereeEmail);
        return $this->respond(200, ['id' => $id], $response);
    }

    /**
     *
     * @SWG\Post(
     *   path="/api/v1/trialInEvent/{trialInEventId}/refereeInOrganization/{refereeInOrganizationId}",
     *   summary="добавляет судью в испытание(локальный админ, Секретарь)",
     *   tags={"Referee"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="trialInEventId", type="integer", description="id испытания в мероприятии, к которому добавляем судью"),
     *   @SWG\Parameter(in="query", name="refereeInOrganizationId", type="integer", description="id судьи в организации"),
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
    public function addRefereeToTrialInEvent(Request $request, Response $response, $args): Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
        $localAdminEmail = $request->getHeader('userEmail')[0];
        $trialInEventId = (int)$args['trialInEventId'];
        $refereeInOrganizationId = (int)$args['refereeInOrganizationId'];

        $access = $this->accessService->hasAccessAddRefereeToTrialOnEvent($userRole, $localAdminEmail, $trialInEventId, $refereeInOrganizationId);
        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            /**@var $access array*/
            return $this->respond(400, ['errors' => $access], $response);
        }

        $id = $this->refereeService->addToTrialOnEvent($refereeInOrganizationId, $trialInEventId);
        return $this->respond(200, ['id' => $id], $response);
    }

    /**
     *
     * @SWG\Delete(
     *   path="/api/v1/refereeInTrialOnEvent/{id}",
     *   summary="Удаляет судью из события в мероприятии(локальный админ, Секретарь)",
     *   tags={"Referee"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id судьи в испытании"),
     *   @SWG\Response(response=200, description="OK"),
     *   @SWG\Response(response=403, description=""),
     *  @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     *
     */
    public function deleteRefereeFromTrialInEvent(Request $request, Response $response, $args): Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
        $userEmail = $request->getHeader('userEmail')[0];
        $refereeInTrialOnEventId = (int)$args['id'];

        $access = $this->accessService->hasAccessDeleteRefereeFromTrialOnEvent($userRole, $userEmail, $refereeInTrialOnEventId);
        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            /**@var $access array*/
            return $this->respond(400, ['errors' => $access], $response);
        }

        $this->refereeService->deleteRefereeFromTrialOnEvent($refereeInTrialOnEventId);
        return $response;
    }

    /**
     *
     * * @SWG\Get(
     *   path="/api/v1/organization/{id}/referee",
     *   summary="получение судей, находящихся в справочнике определенной организации",
     *   tags={"Referee"},
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Response(response=200, description="OK",
     *          @SWG\Schema(ref="#/definitions/refereeOnOrganizationResponse")
     *   ),
     *  @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     */
    public function  get(Request $request, Response $response, $args):Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }
        $organizationId = (int)$args['id'];

        $referiesResponse = [];
        $referies = $this->refereeService->get($organizationId);
        foreach ($referies as $refery){
            $referiesResponse[] = $refery->toArray();
        }

        return $this->respond(200, $referiesResponse, $response);
    }

    /**
     *
     * @SWG\Delete(
     *   path="/api/v1/organization/{id}/referee/{refereeId}",
     *   summary="Удаляет судью из справочника организации(локальный админ)",
     *   tags={"Referee"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Parameter(in="query", name="refereeId", type="integer", description="id судьи"),
     *   @SWG\Response(response=200, description="OK"),
     *   @SWG\Response(response=403, description=""),
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

        $refereeId = (int)$args['refereeId'];
        $userRole = $request->getHeader('userRole')[0];
        $localAdminEmail = $request->getHeader('userEmail')[0];
        $organizationId = (int)$args['id'];

        $access = $this->accessService->hasAccessWorkWithRefereeToOrganization($userRole, $localAdminEmail, $organizationId, $refereeId);
        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            /**@var $access array*/
            return $this->respond(400, ['errors' => $access], $response);
        }

        $this->refereeService->delete($refereeId);
        return $response->withStatus(200);
    }
}