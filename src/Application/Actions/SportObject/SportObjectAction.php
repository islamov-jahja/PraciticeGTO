<?php
namespace App\Application\Actions\SportObject;

use App\Application\Actions\Action;
use App\Application\Middleware\AuthorizeMiddleware;
use App\Domain\Models\SportObject\SportObject;
use App\Services\AccessService\AccessService;
use App\Services\SportObject\SportObjectService;
use App\Validators\SportObject\SportObjectValidator;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class SportObjectAction extends Action
{
    private $sportObjectService;
    private $accessService;
    public function __construct(SportObjectService $sportObjectService, AccessService $accessService)
    {
        $this->sportObjectService = $sportObjectService;
        $this->accessService = $accessService;
    }

    /**
     *
     * @SWG\Post(
     *   path="/api/v1/organization/{id}/sportObject",
     *   summary="добавляет спортивный объеки в справочник организации(локальный админ)",
     *   tags={"SportObject"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Parameter(in="body", name="body", @SWG\Schema(ref="#/definitions/sportObjectRequest")),
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
        $errors = (new SportObjectValidator)->validate($rowParams);
        if (count($errors) > 0) {
            return $this->respond(400, ['errors' => $errors], $response);
        }

        $access = $this->accessService->hasAccessAddSportObjectToOrganization($userRole, $localAdminEmail, $organizationId);
        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            /**@var $access array*/
            return $this->respond(400, ['errors' => $access], $response);
        }
        $sportObject = new SportObject($organizationId, $rowParams['name'], $rowParams['address'], $rowParams['description'], -1);
        $id = $this->sportObjectService->addToOrganization($sportObject);
        return $this->respond(200, ['id' => $id], $response);
    }

    /**
     *
     * @SWG\Delete(
     *   path="/api/v1/organization/{id}/sportObject/{sportObjectId}",
     *   summary="Удаляет спортивный объект из организации(локальный админ)",
     *   tags={"SportObject"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Parameter(in="query", name="sportObjectId", type="integer", description="id спортивного объекта"),
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

        $userRole = $request->getHeader('userRole')[0];
        $localAdminEmail = $request->getHeader('userEmail')[0];
        $organizationId = (int)$args['id'];
        $sportObjectId = (int)$args['sportObjectId'];

        $access = $this->accessService->hasAccessWorkWithSportObject($userRole, $localAdminEmail, $organizationId, $sportObjectId);
        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            /**@var $access array*/
            return $this->respond(400, ['errors' => $access], $response);
        }

        $this->sportObjectService->delete($sportObjectId);
        return $response->withStatus(200);
    }

    /**
     *
     * @SWG\Get(
     *   path="/api/v1/organization/{id}/sportObject",
     *   summary="получает список всех спортивных объектов относящихся к организации",
     *   tags={"SportObject"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Response(response=200, description="OK", @SWG\Property(type="array", @SWG\Items(ref="#/definitions/sportObjectResponse")),
     *  @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     *
     */
    public function get(Request $request, Response $response, $args):Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }
        $userRole = $request->getHeader('userRole')[0];
        $organizationId = (int)$args['id'];

        $sportObjects = $this->sportObjectService->getFilteredByOrganization($organizationId);
        $responseArray = [];
        foreach ($sportObjects as $sportObject) {
            $responseArray[] = $sportObject->toArray();
        }

        return $this->respond(200, $responseArray, $response);
    }

    /**
     *
     * @SWG\Put(
     *   path="/api/v1/organization/{id}/sportObject/{sportObjectId}",
     *   summary="Обновляет данные спортивного объекта(локальный админ)",
     *   tags={"SportObject"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Parameter(in="query", name="sportObjectId", type="integer", description="id спортивного объекта"),
     *   @SWG\Parameter(in="body", name="body", @SWG\Schema(ref="#/definitions/sportObjectRequest")),
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
    public function update(Request $request, Response $response, $args):Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
        $localAdminEmail = $request->getHeader('userEmail')[0];
        $organizationId = (int)$args['id'];
        $sportObjectId = (int)$args['sportObjectId'];

        $rowParams = json_decode($request->getBody()->getContents(), true);
        $errors = (new SportObjectValidator)->validate($rowParams);
        if (count($errors) > 0) {
            return $this->respond(400, ['errors' => $errors], $response);
        }

        $access = $this->accessService->hasAccessWorkWithSportObject($userRole, $localAdminEmail, $organizationId, $sportObjectId);
        if ($access === false){
            return $response->withStatus(403);
        }else if ($access !== true){
            /**@var $access array*/
            return $this->respond(400, ['errors' => $access], $response);
        }
        $sportObject = new SportObject($organizationId, $rowParams['name'], $rowParams['address'], $rowParams['description'], $sportObjectId);
        $this->sportObjectService->update($sportObject);
        return $response;
    }
}