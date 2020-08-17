<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 04.11.2019
 * Time: 20:37
 */

namespace App\Persistance\Repositories\User;
use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Domain\Models\Organization;
use App\Domain\Models\Role\RoleNotFoundException;
use App\Domain\Models\User\User;
use App\Domain\Models\User\UserCreater;
use App\Persistance\ModelsEloquant\Organization\Organization as OrgPDO;
use App\Persistance\ModelsEloquant\User\User as UserElaquent;
use App\Persistance\ModelsEloquant\User\User as UserPDO;
use App\Persistance\Repositories\Role;
use App\Services\Token\Token;
use Illuminate\Support\Facades\Date;
use Symfony\Component\Validator\Constraints\DateTime;
use App\Persistance\Repositories\Role\RoleRepository;
use TypeError;

class UserRepository implements IRepository
{
    public function userIsSetOnDBWithEmail($email)
    {
        if (UserElaquent::query()->where('email', '=', $email)->count() == 0){
            return false;
        }

        return true;
    }

    public function userIsSetOnDb($email, $password)
    {
        if (UserElaquent::query()
                ->where('email', '=', $email)
                ->where('password', '=', $password)
                ->count() == 0){
            return false;
        }

        return true;
    }

    public function getRoleOfUser($email)
    {
        $roles = UserElaquent::query()
            ->leftJoin('role', 'user.role_id', '=', 'role.role_id')
            ->where('user.email', '=', $email)
            ->get();

        return $roles[0]->name_of_role;
    }

    /**@var $user User*/
    public function add(IModel $user):int
    {
        if (!($user instanceof User)){
            throw new TypeError();
        }

        $roleRep = new RoleRepository();
        $roles = $roleRep->get($user->getRoleId());

        if ($roles == null){
            throw new RoleNotFoundException('role not found');
        }

        $userId = UserElaquent::query()->create([
            'name' => $user->getName(),
            'password' => $user->getPassword(),
            'email' => $user->getEmail(),
            'role_id' => $user->getRoleId(),
            'is_activity' => $user->isActivity(),
            'registration_date' => $user->getRegistrationDate(),
            'gender' => $user->getGender(),
            'date_of_birth' => $user->getDateOfBirth()
        ])->getAttribute('user_id');

        return $userId;
    }

    /**@return User*/
    public function get(int $id): ?IModel
    {
        $userElaquent = UserElaquent::query()->where('user_id', '=', $id)->get();

        if (count($userElaquent) == 0){
            return null;
        }

        return UserCreater::createModel([
            'id' => $userElaquent[0]['user_id'],
            'name' => $userElaquent[0]['name'],
            'password' => $userElaquent[0]['password'],
            'email' => $userElaquent[0]['email'],
            'roleId' => $userElaquent[0]['role_id'],
            'isActivity' => $userElaquent[0]['is_activity'],
            'dateTime' => new \DateTime($userElaquent[0]['registration_date']),
            'gender' => $userElaquent[0]['gender'],
            'dateOfBirth' => new \DateTime($userElaquent[0]['date_of_birth'])
        ]);
    }

    public function getByEmail(string $email):?User
    {
        $userElaquent = UserElaquent::query()->where('email', '=', $email)->get();
        if (count($userElaquent) == 0){
            return null;
        }

        return UserCreater::createModel([
            'id' => $userElaquent[0]['user_id'],
            'name' => $userElaquent[0]['name'],
            'password' => $userElaquent[0]['password'],
            'email' => $userElaquent[0]['email'],
            'roleId' => $userElaquent[0]['role_id'],
            'isActivity' => $userElaquent[0]['is_activity'],
            'dateTime' => new \DateTime($userElaquent[0]['registration_date']),
            'gender' => $userElaquent[0]['gender'],
            'dateOfBirth' => new \DateTime($userElaquent[0]['date_of_birth'])
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        // TODO: Implement getAll() method.
    }

    public function delete(int $id)
    {
        UserPDO::query()
            ->where('user_id', '=', $id)
            ->delete();
    }

    /**@var $user User*/
    public function update(IModel $user)
    {
        UserPDO::query()->where('user_id', '=', $user->getId())->update([
            'name' => $user->getName(),
            'password' => $user->getPassword(),
            'email' => $user->getEmail(),
            'role_id' => $user->getRoleId(),
            'is_activity' => $user->isActivity(),
            'gender' => $user->getGender(),
            'date_of_birth' => $user->getDateOfBirth(),
            'uid' => $user->getUid()
            ]);
    }
}