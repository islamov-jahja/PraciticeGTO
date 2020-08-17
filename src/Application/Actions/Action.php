<?php
declare(strict_types=1);

namespace App\Application\Actions;
use App\Application\Middleware\AuthorizeMiddleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validation;
use Psr\Http\Message\ResponseInterface as Response;

abstract class Action
{
    protected function respond(int $status, array $data = null, Response $response):Response
    {
        if ($data){
            $response->getBody()->write(json_encode($data));
        }

        return $response->withStatus($status);
    }

    protected function tokenWithError(ResponseInterface $response, RequestInterface $request):bool
    {
        $errors = $request->getHeader('error');
        if (count($errors) != 0) {
            $error = new ActionError(ActionError::BAD_REQUEST, $errors[0]);
            $error->jsonSerialize();
            $response->getBody()->write(json_encode(['errors' => array($error->jsonSerialize())]));
            return true;
        }

        return false;
    }
}
