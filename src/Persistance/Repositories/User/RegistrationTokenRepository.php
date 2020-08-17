<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 04.11.2019
 * Time: 16:07
 */

namespace App\Persistance\Repositories\User;
use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Domain\Models\Organization;
use App\Domain\Models\Token\RegistrationToken;
use App\Persistance\ModelsEloquant\RegistrationToken\RegistrationToken as Token;

class RegistrationTokenRepository implements IRepository
{
    public function addTokenToDB($token):void
    {
        Token::query()->create([
            'token' => $token,
            'dateTimeToDelete' => (new \DateTime('+1 day'))
                ->setTimezone(new \DateTimeZone('europe/moscow'))
                ->format('Y-m-d H:i:s')
        ]);
    }

    public function cleanOldTokens()
    {

    }

    public function getByTokenValue(string $token):?RegistrationToken
    {
       $token = Token::query()->where('token', '=', $token)->get();
       if ($token == null){
           return null;
       }

       return new RegistrationToken($token[0]['registration_token_id'], $token[0]['token'], new \DateTime($token[0]['dateTimeToDelete']));

    }

    public function deleteTokenFromDB(string $token):void
    {
        Token::query()->where('token', '=', $token)->delete();
    }

    public function get(int $id): IModel
    {
        // TODO: Implement get() method.
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        // TODO: Implement getAll() method.
    }

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