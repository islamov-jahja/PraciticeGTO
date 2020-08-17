<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 03.11.2019
 * Time: 22:30
 */

namespace App\Persistance\Repositories\Role;
use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Domain\Models\Organization;
use App\Persistance\ModelsEloquant\Role\Role;
use Monolog\Logger;
use App\Domain\Models\Role\Role as ModelRole;

class RoleRepository implements IRepository
{
    public function getAll():array
    {
        $roles = [];
        $roleSql = Role::query()->where('name_of_role', '!=', 'Глобальный администратор')->get();
        foreach ($roleSql as $role){
            $roles[] = $role;
        }

        return $roles;
    }

    public function get(int $id): ?IModel
    {
        $roleSql = Role::query()->where('role_id', '=', $id)->get();

        if (count($roleSql) == 0){
            return null;
        }

        return new ModelRole($id, $roleSql[0]['name_of_role']);
    }

    public function getByName(string $name): ?IModel
    {
        $roleSql = Role::query()->where('name_of_role', '=', $name)->get();

        if (count($roleSql) == 0){
            return null;
        }

        return new ModelRole($roleSql[0]['role_id'], $roleSql[0]['name_of_role']);
    }

    /**
     * @inheritDoc
     */


    public function add(IModel $model):int
    {
        // TODO: Implement add() method.
    }

    public function delete(int $id)
    {
        // TODO: Implement delete() method.
    }

    public function update(IModel $organization)
    {
        // TODO: Implement update() method.
    }
}