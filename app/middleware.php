<?php
declare(strict_types=1);

use App\Application\Middleware\AuthorizeMiddleware;
use Slim\App;

return function (App $app) {
    $app->add(AuthorizeMiddleware::class);
};
