<?php

namespace App\Application\Actions\Secretary;
use App\Application\Actions\Action;
use App\Application\Middleware\AuthorizeMiddleware;
use App\Domain\Models\Secretary\SecretaryOnOrganization;
use App\Services\AccessService\AccessService;
use App\Services\Secretary\SecretaryService;
use App\Validators\Auth\EmailValidator;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class SecretaryAction extends Action
{
    private $secretaryService;
    private $accessService;

    public function __construct(SecretaryService $secretaryService, AccessService $accessService)
    {
        $this->secretaryService = $secretaryService;
        $this->accessService = $accessService;
    }

    /**
     *
     * @SWG\Post(
     *   path="/api/v1/organization/{id}/secretary",
     *   summary="добавляет секретаря, из ранее существующих аккаунтов в справочник организации(локальный админ)",
     *   tags={"Secretary"},
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


    public function addToOrganization(Request $request, Response $response, $args):Response
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

        $secretaryEmail = $rowParams['email'];

        $access = $this->accessService->hasAccessAddSecretaryToOrganization($userRole, $localAdminEmail, $organizationId, $secretaryEmail);
        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            /**@var $access array*/
            return $this->respond(400, ['errors' => $access], $response);
        }

        $id = $this->secretaryService->addToOrganization($organizationId, $secretaryEmail);
        return $this->respond(200, ['id' => $id], $response);
    }

    /**
     *
     * @SWG\Delete(
     *   path="/api/v1/organization/{id}/secretary/{secretaryId}",
     *   summary="удаляет секретаря из справочника своей организации(локальный админ)",
     *   tags={"Secretary"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Parameter(in="query", name="secretaryId", type="integer", description="id секретаря"),
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
    public function deleteFromOrganization(Request $request, Response $response, $args):Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
        $localAdminEmail = $request->getHeader('userEmail')[0];
        $organizationId = (int)$args['id'];
        $secretaryId = (int)$args['secretaryId'];
        $access = $this->accessService->hasAccessDeleteSecretaryFromOrganization($userRole, $localAdminEmail, $organizationId, $secretaryId);
        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            /**@var $access array*/
            return $this->respond(400, ['errors' => $access], $response);
        }

        $this->secretaryService->deleteFromOrganization($secretaryId);
        return  $response->withStatus(200);
    }

    /**
     *
     * * @SWG\Get(
     *   path="/api/v1/organization/{id}/secretary",
     *   summary="получение секретайрей, относящихся к определенной организации",
     *   tags={"Secretary"},
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Response(response=200, description="OK",
     *          @SWG\Schema(ref="#/definitions/secretaryOnOrganizationResponse")
     *   ),
     *  @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     */
    public function getSecretariesOnOrganization(Request $request, Response $response, $args):Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }

        $organizationId = (int)$args['id'];
        /**@var $secretaries SecretaryOnOrganization[]*/
        $secretaries = $this->secretaryService->getSecretariesOnOrganization($organizationId);
        $secretariesOnArray = [];
        foreach ($secretaries as $secretary) {
            $secretariesOnArray[] = $secretary->toArray();
        }

        return $this->respond(200, $secretariesOnArray, $response);
    }

    /**
     *
     * @SWG\Post(
     *   path="/api/v1/organization/{id}/event/{eventId}/secretary/{secretaryOnOrganizationId}",
     *   summary="добавляет секретаря в мероприятие по его id из справочника(локальный админ)",
     *   tags={"Secretary"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Parameter(in="query", name="eventId", type="integer", description="id мероприятия, право не редактирование которого добавляем"),
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

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function addToEvent(Request $request, Response $response, $args):Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }
        $userRole = $request->getHeader('userRole')[0];
        $localAdminEmail = $request->getHeader('userEmail')[0];

        if ($userRole != AuthorizeMiddleware::LOCAL_ADMIN){
            return $response->withStatus(403);
        }
        $secretaryInOrgId = (int)$args['secretaryOnOrganizationId'];
        $secretaryId = $this->secretaryService->addToEvent($localAdminEmail, $secretaryInOrgId, (int)$args['id'], (int)$args['eventId'], $response);

        if ($secretaryId instanceof  Response){
            return $secretaryId;
        }
        return $this->respond(200, ['id' => $secretaryId], $response);
    }

    /**
     *
     * * @SWG\Get(
     *   path="/api/v1/organization/{id}/event/{eventId}/secretary",
     *   summary="получение секретайрей, относящихся к определенному меропритию",
     *   tags={"Secretary"},
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Parameter(in="query", name="eventId", type="integer", description="id мероприятия"),
     *   @SWG\Response(response=200, description="OK",
     *          @SWG\Schema(ref="#/definitions/secretaryResponse")
     *   ),
     *  @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     */

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function get(Request $request, Response $response, $args):Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }
        $userRole = $request->getHeader('userRole')[0];
        $localAdminEmail = $request->getHeader('userEmail')[0];

        if ($userRole != AuthorizeMiddleware::LOCAL_ADMIN){
            return $response->withStatus(403);
        }

        $secretaries = $this->secretaryService->get((int)$args['id'], (int)$args['eventId'], $localAdminEmail, $response);
        if ($secretaries == null){
            return $this->respond(200, [], $response);
        }

        if ($secretaries instanceof  Response){
            return $secretaries;
        }

        $secretariesInArray = [];

        foreach ($secretaries as $secretary){
            $secretariesInArray[] = $secretary->toArray();
        }

        return $this->respond(200, $secretariesInArray, $response);
    }

    /**
     *
     * * @SWG\Delete(
     *   path="/api/v1/organization/{id}/event/{eventId}/secretary{secretaryId}",
     *   summary="удаляет секретаря от определенной организации(локальный админ)",
     *   tags={"Secretary"},
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Parameter(in="query", name="eventId", type="integer", description="id мероприятия, от которого удаляем секретаря"),
     *   @SWG\Parameter(in="query", name="secretaryId", type="integer", description="id секретаря, которого удаляем"),
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
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

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function delete(Request $request, Response $response, $args):Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
        $localAdminEmail = $request->getHeader('userEmail')[0];

        if ($userRole != AuthorizeMiddleware::LOCAL_ADMIN){
            return $response->withStatus(403);
        }

        $result = $this->secretaryService->delete((int)$args['id'], (int)$args['eventId'], (int)$args['secretaryId'], $localAdminEmail, $response);

        if ($result instanceof Response){
            return $result;
        }

        return  $response;
    }
}