<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17.10.2019
 * Time: 10:57
 */

namespace App\Application\Actions\Swagger;


use App\Application\Actions\Action;
use App\Domain\DomainException\DomainRecordNotFoundException;
use App\Swagger\SwaggerWatcher;
use Slim\Exception\HttpBadRequestException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SwaggerAction extends Action
{
    private $pathToProject;

    public function __construct($pathToProject)
    {
        $this->pathToProject = $pathToProject;
    }

    /**
     * @return Response
     * @throws DomainRecordNotFoundException
     * @throws HttpBadRequestException
     */
    public function getNewDocs(Request $request, Response $response, $args): Response
    {
        $swaggerWatcher = new SwaggerWatcher($this->pathToProject);
        $response->getBody()->write($swaggerWatcher->getDocumentation());
        return $response;
    }
}