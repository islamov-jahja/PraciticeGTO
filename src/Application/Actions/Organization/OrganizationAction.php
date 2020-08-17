<?php

namespace App\Application\Actions\Organization;

use App\Application\Actions\Action;
use App\Application\Actions\ActionError;
use App\Application\Middleware\AuthorizeMiddleware;
use App\Services\Presenters\OrganizationsToResponsePresenter;
use App\Validators\Organization\OrganizationObjectValidator;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\Models\OrganizationCreater;
use App\Services\Organization\OrganizationService;

class OrganizationAction extends Action
{
    private $organizationService;

    public function __construct(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    /**
     *
     * @SWG\Post(
     *   path="/api/v1/organization",
     *   summary="добавляет организацию(глобальный админ)",
     *   tags={"Organization"},
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="body", name="body", @SWG\Schema(ref="#/definitions/OrganizationRequest")),
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

        $rowParams = json_decode($request->getBody()->getContents(), true);
        $rowParams['id'] = -1;
        $userRole = $request->getHeader('userRole')[0];

        if ($userRole != AuthorizeMiddleware::GLOBAL_ADMIN){
            return $response->withStatus(403);
        }

        $errors = (new OrganizationObjectValidator())->validate($rowParams);
        if (count($errors) > 0){
            return $this->respond(400, ['errors' => $errors], $response);
        }

        $id = $this->organizationService->addOrganization(OrganizationCreater::createModel($rowParams));

        if ($id == -1){
            $error = new ActionError(ActionError::BAD_REQUEST, 'Организация с таким названием существует');
            return $this->respond(400, ['errors' => array($error->jsonSerialize())], $response);
        }

        return $this->respond(200, ['id' => $id], $response);
    }
    /**
     *
     * * @SWG\Get(
     *   path="/api/v1/organization/{id}",
     *   summary="получение организации по id",
     *   tags={"Organization"},
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Response(response=200, description="OK",
     *          @SWG\Schema(ref="#/definitions/OrganizationResponse")
     *   ),
     *  @SWG\Response(response=404, description="Not found"),
     *  @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     *
     */
    public function get(Request $request, Response $response, $args): Response
    {
        $id = $args['id'];
        $organization = $this->organizationService->getOrganization($id);
        if ($organization == null){
            return $this->respond(404, [], $response);
        }

        return $this->respond(200, $organization->toArray(), $response);
    }

    /**
     *
     * * @SWG\Get(
     *   path="/api/v1/organization",
     *   summary="получение все существующие организации",
     *   tags={"Organization"},
     *   @SWG\Response(response=200, description="OK",
     *          @SWG\Property(type="array", @SWG\Items(ref="#/definitions/OrganizationResponse"))
     *   ),
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
        $organizations = $this->organizationService->getOrganizations();
        if ($organizations == null){
            $this->respond(200, [], $response);
        }

        return $this->respond(200, OrganizationsToResponsePresenter::getView($organizations), $response);
    }

    /**
     *
     * * @SWG\Delete(
     *   path="/api/v1/organization/{id}",
     *   summary="удаляет организацию по id(глобальный админ)",
     *   tags={"Organization"},
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Response(response=200, description="OK")
     *   ),
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

        if ($request->getHeader('userRole')[0] != AuthorizeMiddleware::GLOBAL_ADMIN){
            return $response->withStatus(403);
        }

        $id = $args['id'];
        $this->organizationService->deleteOrganization($id);
        return $response;
    }

    /**
     *
     * @SWG\Put(
     *   path="/api/v1/organization/{id}",
     *   summary="обновляет данные об организации, id которого передан(глобальный админ)",
     *   tags={"Organization"},
     *   @SWG\Parameter(in="query", name="id", type="integer", description="id организации"),
     *   @SWG\Parameter(in="header", name="Authorization", type="string", description="токен"),
     *   @SWG\Parameter(in="body", name="body", @SWG\Schema(ref="#/definitions/OrganizationRequest")),
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
    public function update(Request $request, Response $response, $args): Response
    {
        if ($this->tokenWithError($response, $request)){
            return $response->withStatus(401);
        }

        $userRole = $request->getHeader('userRole')[0];
        if ($userRole != AuthorizeMiddleware::GLOBAL_ADMIN){
            return $response->withStatus(403);
        }

        $id = (int)$args['id'];
        $rowParams = json_decode($request->getBody()->getContents(), true);
        $rowParams['id'] = $id;

        $errors = (new OrganizationObjectValidator())->validate($rowParams);
        if (count($errors) > 0){
            return $this->respond(400, ['errors' => $errors], $response);
        }

        $organization = OrganizationCreater::createModel($rowParams);
        $result = $this->organizationService->update($organization);

        if ($result == -1){
            $error = new ActionError(ActionError::BAD_REQUEST, 'Организация с таким названием существует');
            return $this->respond(400, ['errors' => array($error->jsonSerialize())], $response);
        }

        return $response;
    }
}