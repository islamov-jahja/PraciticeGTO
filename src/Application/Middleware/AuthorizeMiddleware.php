<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use App\Services\Logger;
use App\Services\Token\Token;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AuthorizeMiddleware implements Middleware
{
    /**
     * {@inheritdoc}
     */
    const UNAUTHORIZED_USER = 'UnauthorizedUser';
    const GLOBAL_ADMIN = 'Глобальный администратор';
    const LOCAL_ADMIN = 'Локальный администратор';
    const SIMPLE_USER = 'Простой пользователь';
    const SECRETARY = 'Секретарь';
    const TEAM_LEAD = 'Тренер';

    public function process(Request $request, RequestHandler $handler): Response
    {
        $token = $request->getHeader('Authorization')[0] ?? '';

        $config = json_decode(file_get_contents(__DIR__.'/../../../config.json'), true);
        Token::$key = $config['Token']['key'];
        try{
            $tokenInArray = (array)Token::getDecodedToken($token);
            if (Token::isOldToken($tokenInArray)){
                $request = $request->withHeader('error', 'ваш токен устарел');
            }
            $request = $request->withHeader('userEmail', $tokenInArray['email'] ?? '');
            $request = $request->withHeader('name', $tokenInArray['name'] ?? '');
            $request = $request->withHeader('gender', $tokenInArray['gender'] ?? '');
            $request = $request->withHeader('dateOfBirth', $tokenInArray['dateOfBirth'] ?? '');
            $request = $request->withHeader('userRole', $tokenInArray['role']  ?? '');

        }catch (Exception $err){
            $request = $request->withHeader('error', 'не валидный токен');
            $request = $request->withHeader('userRole', self::UNAUTHORIZED_USER);
        }

        return $handler->handle($request);
    }
}
