<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 28.11.2019
 * Time: 9:54
 */

namespace App\Services\Role;
use Psr\Http\Message\ResponseInterface as Response;

use App\Persistance\Repositories\Role\RoleRepository;

class Role
{
    private $roleRepos;

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepos = $roleRepository;
    }

    public function getList(array $params, Response $response):Response
    {
        $response->getBody()->write(json_encode(['roles' => $this->roleRepos->getAll()]));
        return $response;
    }
}